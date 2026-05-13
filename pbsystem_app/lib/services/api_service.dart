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
  static int _currentUserId = 0;
  static String _currentUserName = '';
  static String _currentUserEmail = '';
  static String _currentUserRole = 'user';

  static Future<void> configure() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null && saved.trim().isNotEmpty) {
      _baseUrl = saved.trim();
    }
    _webSessionCookie = prefs.getString(_prefsWebCookieKey) ?? '';
    _webSessionRole = prefs.getString(_prefsWebRoleKey)?.toLowerCase() ?? 'user';
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

  static int get currentUserId => _currentUserId;
  static String get currentUserName => _currentUserName;
  static String get currentUserEmail => _currentUserEmail;
  static String get currentUserRole => _currentUserRole;

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
    _webSessionRole = role.toLowerCase();
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_prefsWebCookieKey, cookie);
    await prefs.setString(_prefsWebRoleKey, _webSessionRole);
  }

  static void _cacheCurrentUser({
    required int id,
    required String name,
    required String email,
    required String role,
  }) {
    _currentUserId = id;
    _currentUserName = name;
    _currentUserEmail = email;
    _currentUserRole = role.toLowerCase();
  }

  Future<void> _bootstrapWebSession(
    String role,
    String email,
    String password,
  ) async {
    final finalRole = role.toLowerCase();
    final endpoint = finalRole == 'admin' ? 'loginAdmin.php' : 'login.php';

    final client = http.Client();
    try {
      Future<http.Response> sendRequest(Map<String, String> bodyFields) async {
        final request = http.Request('POST', _webUri(endpoint))
          ..followRedirects = false
          ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
          ..bodyFields = bodyFields;

        try {
          final streamed = await client.send(request);
          return await http.Response.fromStream(streamed);
        } on SocketException catch (e) {
          final isHostLookup = e.message.toLowerCase().contains('failed host lookup');
          final currentHost = _webUri(endpoint).host;
          final shouldRetry = isHostLookup &&
              currentHost.isNotEmpty &&
              currentHost != Uri.parse(_fallbackBaseUrl).host;

          if (!shouldRetry) rethrow;

          final fallbackUri = Uri.parse(_fallbackBaseUrl).replace(
            path: _webUri(endpoint).path,
            queryParameters: _webUri(endpoint).hasQuery ? _webUri(endpoint).queryParameters : null,
          );

          final fallbackRequest = http.Request('POST', fallbackUri)
            ..followRedirects = false
            ..headers['Content-Type'] = 'application/x-www-form-urlencoded'
            ..headers['Host'] = _fallbackHostHeader
            ..bodyFields = bodyFields;

          final streamed = await client.send(fallbackRequest);
          return await http.Response.fromStream(streamed);
        }
      }

      // First try role-specific field names
      final primaryFields = finalRole == 'admin'
          ? {'email_Admin': email, 'password_Admin': password}
          : {'email': email, 'password': password};

      var response = await sendRequest(primaryFields);
      var setCookie = response.headers['set-cookie'] ?? '';
      var sessionMatch = RegExp(r'PHPSESSID=([^;]+)').firstMatch(setCookie);

      if (sessionMatch == null) {
        // Try alternate field names in case the form expects generic names
        final altFields = finalRole == 'admin'
            ? {'email': email, 'password': password}
            : {'email_Admin': email, 'password_Admin': password};

        response = await sendRequest(altFields);
        setCookie = response.headers['set-cookie'] ?? '';
        sessionMatch = RegExp(r'PHPSESSID=([^;]+)').firstMatch(setCookie);

        if (sessionMatch == null) {
          throw StateError('Web session cookie missing');
        }
      }

      await _persistWebSession(sessionMatch.group(1)!, finalRole);
    } finally {
      client.close();
    }
  }

  // HTML parsing fallback removed. App now prefers JSON endpoints only.
  // Legacy HTML parser was kept as diagnostic previously, but it's removed to simplify
  // the code path. If JSON endpoints are not deployed, the app will fall back to the
  // legacy JSON API (search_car_user_api.php) instead of parsing HTML.

  /// ================= LOGIN =================
  Future<Map<String, dynamic>> login(
      String email, String password, String role) async {
    try {
      role = role.toLowerCase();
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
          final admin = data['admin'];
          _cacheCurrentUser(
            id: int.tryParse(admin['id'].toString()) ?? 0,
            name: admin['name']?.toString() ?? '',
            email: admin['email']?.toString() ?? '',
            role: 'admin',
          );
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
          final user = data['user'];
          _cacheCurrentUser(
            id: int.tryParse(user['id'].toString()) ?? 0,
            name: user['name']?.toString() ?? '',
            email: user['email']?.toString() ?? '',
            role: role,
          );
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
      // Select category-specific JSON endpoint when possible
      String endpoint = 'search_api.php';
      final s = status.toLowerCase();
      if (s.contains('staf') || s.contains('staff')) {
        endpoint = 'search_staff_api.php';
      } else if (s.contains('pelajar') || s.contains('student')) {
        endpoint = 'search_students_api.php';
      } else if (s.contains('pelawat') || s.contains('visitor')) {
        endpoint = 'search_visitors_api.php';
      } else if (s.contains('kontraktor') || s.contains('contractor')) {
        endpoint = 'search_contractors_api.php';
      } else {
        endpoint = 'search_api.php';
      }

      http.Response? response;
      try {
        response = await _post(
          _uri(endpoint),
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
      } catch (e) {
        // network/host error - will try legacy endpoint
      }

      // Legacy API fallback (if new endpoints not deployed)
      final legacyResp = await _post(
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

      if (legacyResp.statusCode == 200 && legacyResp.body.trim().isNotEmpty) {
        final jsonData = jsonDecode(legacyResp.body);
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
        'message': 'Server error: ${legacyResp?.statusCode ?? response?.statusCode ?? 'unknown'}'
      };
    } catch (e) {
      return {
        'success': 0,
        'message': 'API error: $e'
      };
    }
  }
}
