// lib/services/api_service.dart
import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  static String _baseUrl = String.fromEnvironment(
    'API_BASE_URL',
    defaultValue: 'http://neovtrack.uitm.edu.my',
  );
  static const String _fallbackBaseUrl = 'http://10.0.26.208';
  static const String _fallbackHostHeader = 'neovtrack.uitm.edu.my';
  static const String _prefsWebCookieKey = 'web_session_cookie';
  static const String _prefsWebRoleKey = 'web_session_role';

  static const String _prefsKey = 'api_base_url';
  static String _webSessionCookie = '';
  static String _webSessionRole = 'user';

  static Future<void> configure() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null && saved.trim().isNotEmpty) {
      _baseUrl = saved.trim();
    }
    _webSessionCookie = prefs.getString(_prefsWebCookieKey) ?? '';
    _webSessionRole = prefs.getString(_prefsWebRoleKey) ?? 'user';
  }

  static Future<void> setBaseUrl(String url) async {
    final normalized = url.trim().replaceAll(RegExp(r'/$'), '');
    if (normalized.isEmpty) {
      return;
    }

    _baseUrl = normalized;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsKey, _baseUrl);
  }

  static String get baseUrl => _baseUrl;

  static String get webSessionCookie => _webSessionCookie;

  static String get webSessionRole => _webSessionRole;

  Uri _uri(String path) => Uri.parse('$baseUrl/$path');

  Uri _webUri(String path) => Uri.parse('$baseUrl/$path');

  Future<http.Response> _post(Uri url, Map<String, String> headers, Object? body) async {
    try {
      return await http.post(url, headers: headers, body: body);
    } on SocketException catch (e) {
      final isHostLookup = e.message.toLowerCase().contains('failed host lookup');
      final currentHost = url.host;
      final shouldRetry = isHostLookup &&
          currentHost.isNotEmpty &&
          currentHost != Uri.parse(_fallbackBaseUrl).host;

      if (!shouldRetry) {
        rethrow;
      }

      final fallbackUri = Uri.parse(_fallbackBaseUrl).replace(
        path: url.path,
        queryParameters: url.hasQuery ? url.queryParameters : null,
      );

      final fallbackHeaders = Map<String, String>.from(headers);
      fallbackHeaders['Host'] = _fallbackHostHeader;

      print('API fallback - retrying via IP: $fallbackUri');
      return await http.post(fallbackUri, headers: fallbackHeaders, body: body);
    }
  }

  Future<void> _persistWebSession(String cookie, String role) async {
    _webSessionCookie = cookie;
    _webSessionRole = role;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsWebCookieKey, cookie);
    await prefs.setString(_prefsWebRoleKey, role);
  }

  Future<void> _bootstrapWebSession(
    String role,
    String email,
    String password,
  ) async {
    final endpoint = role == 'admin' ? 'loginAdmin.php' : 'login.php';
    final fields = role == 'admin'
        ? {
            'email_Admin': email,
            'password_Admin': password,
          }
        : {
            'email': email,
            'password': password,
          };

    final client = http.Client();
    try {
      final request = http.Request('POST', _webUri(endpoint))
        ..followRedirects = false
        ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
        ..bodyFields = fields;

      final streamed = await client.send(request);
      final response = await http.Response.fromStream(streamed);

      final setCookie = response.headers['set-cookie'] ?? '';
      final sessionMatch = RegExp(r'PHPSESSID=([^;]+)').firstMatch(setCookie);
      if (sessionMatch == null) {
        throw StateError('Web session cookie missing');
      }

      await _persistWebSession(sessionMatch.group(1)!, role);
    } finally {
      client.close();
    }
  }

  String _stripHtml(String input) {
    var text = input
        .replaceAll(RegExp(r'<br\s*/?>', caseSensitive: false), ' ')
        .replaceAll(RegExp(r'<[^>]+>'), ' ')
        .replaceAll('&nbsp;', ' ')
        .replaceAll('&amp;', '&')
        .replaceAll('&quot;', '"')
        .replaceAll('&#39;', "'")
        .replaceAll(RegExp(r'\s+'), ' ')
        .trim();
    return text;
  }

  List<Map<String, dynamic>> _parseSearchRows(String html) {
    final tableMatch = RegExp(
      r'<table[^>]*id="vehicleTable"[^>]*>.*?<tbody>(.*?)</tbody>',
      caseSensitive: false,
      dotAll: true,
    ).firstMatch(html);

    if (tableMatch == null) {
      return [];
    }

    final tbody = tableMatch.group(1) ?? '';
    final rows = <Map<String, dynamic>>[];
    final rowMatches = RegExp(
      r'<tr>(.*?)</tr>',
      caseSensitive: false,
      dotAll: true,
    ).allMatches(tbody);

    for (final rowMatch in rowMatches) {
      final rowHtml = rowMatch.group(1) ?? '';
      final cellMatches = RegExp(
        r'<td[^>]*>(.*?)</td>',
        caseSensitive: false,
        dotAll: true,
      ).allMatches(rowHtml);

      final cells = cellMatches
          .map((cell) => _stripHtml(cell.group(1) ?? ''))
          .toList();

      if (cells.length < 7) {
        continue;
      }

      final data = <String, dynamic>{
        'status': cells[1],
        'idnumber': cells[2],
        'name': cells[3],
        'phone': cells[4],
        'platenum': cells[5].toUpperCase(),
        'type': cells[6],
      };

      if (cells.length > 7) {
        data['sticker'] = cells[7];
      }

      rows.add(data);
    }

    return rows;
  }

  /// ================= LOGIN =================
  Future<Map<String, dynamic>> login(
      String email, String password, String role) async {
    try {
      final endpoint =
          role == 'admin' ? 'login_admin_api.php' : 'login_user_api.php';
      final url = _uri(endpoint);

      // Debug logging
      print('API Login - URL: $url');
      print('API Login - Email: $email');

      final response = await _post(
        url,
        {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        {
          'email': email,
          'password': password,
        },
      );

      print('API Login - Status Code: ${response.statusCode}');
      print('API Login - Response Body: ${response.body}');

      if (response.statusCode == 200 &&
          response.body.trim().isNotEmpty) {
        final data = jsonDecode(response.body);

        // Wrap admin data into user format for consistency
        if (role == 'admin' &&
            data['success'] == 1 &&
            data['admin'] != null) {
          try {
            await _bootstrapWebSession(role, email, password);
          } catch (e) {
            print('Web session bootstrap failed: $e');
          }
          return {
            'success': 1,
            'user': {
              'id': data['admin']['id'],
              'name': data['admin']['name'],
              'email': data['admin']['email'],
            }
          };
        }

        if (role != 'admin' && data['success'] == 1 && data['user'] != null) {
          try {
            await _bootstrapWebSession(role, email, password);
          } catch (e) {
            print('Web session bootstrap failed: $e');
          }
        }

        return data;
      } else {
        return {
          'success': 0,
          'message': 'Server returned empty response (Status: ${response.statusCode})'
        };
      }
    } catch (e) {
      print('API Login - Exception: $e');
      return {
        'success': 0,
        'message': 'API error: $e'
      };
    }
  }

  /// ================= REGISTER =================
  Future<Map<String, dynamic>> register(
      String name,
      String email,
      String password,
      String confirmPassword) async {
    try {
      final response = await _post(
        _uri('register_user_api.php'),
        {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        {
          'name': name,
          'email': email,
          'password': password,
          'confirm_password': confirmPassword,
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {
          'success': 0,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': 0,
        'message': 'API error: $e'
      };
    }
  }

  /// ================= RESET PASSWORD =================
  Future<Map<String, dynamic>> resetPassword(
      String email,
      String password,
      String confirmPassword) async {
    try {
      final response = await _post(
        _uri('forgot_password_api.php'),
        {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        {
          'step': '2',
          'email': email,
          'password': password,
          'confirm_password': confirmPassword,
        },
      );

      if (response.statusCode == 200) {
        return jsonDecode(response.body);
      } else {
        return {
          'success': 0,
          'message': 'Server error: ${response.statusCode}'
        };
      }
    } catch (e) {
      return {
        'success': 0,
        'message': 'API error: $e'
      };
    }
  }

  /// ================= SEARCH VEHICLES =================
  Future<Map<String, dynamic>> searchCarUser({
    String search = '',
    String status = '',
    bool showAll = false,
  }) async {
    try {
      final searchPage = webSessionRole == 'admin'
          ? 'searchCar.php'
          : 'searchCarUser.php';

      if (webSessionCookie.isNotEmpty) {
        final client = http.Client();
        try {
          final request = http.Request('POST', _webUri(searchPage))
            ..followRedirects = false
            ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
            ..headers['Cookie'] = 'PHPSESSID=$webSessionCookie'
            ..bodyFields = {
              'search': search,
              'status': status,
              'showAll': showAll ? 'true' : 'false',
              'submit': '1',
            };

          final streamed = await client.send(request);
          final response = await http.Response.fromStream(streamed);

          if (response.statusCode == 200 && response.body.trim().isNotEmpty) {
            final rows = _parseSearchRows(response.body);
            return {
              'success': 1,
              'count': rows.length,
              'data': rows,
              'message': rows.isEmpty ? 'No vehicles found' : '',
            };
          }
        } finally {
          client.close();
        }
      }

      final response = await _post(
        _uri('search_car_user_api.php'),
        {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        {
          'search': search,
          'status': status,
          'showAll': showAll ? 'true' : 'false',
        },
      );

      if (response.statusCode == 200 && response.body.trim().isNotEmpty) {
        final jsonData = jsonDecode(response.body);
        final data = (jsonData['data'] ?? jsonData['vehicles'] ?? []) as List;

        return {
          'success': jsonData['success'] ?? 0,
          'count': jsonData['count'] ?? data.length,
          'data': data,
          'message': jsonData['message'] ?? '',
        };
      }

      return {
        'success': 0,
        'message': 'Server error: ${response.statusCode}'
      };
    } catch (e) {
      return {
        'success': 0,
        'message': 'API error: $e'
      };
    }
  }
}
