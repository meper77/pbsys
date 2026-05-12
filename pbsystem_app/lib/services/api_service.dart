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

  static const String _prefsKey = 'api_base_url';

  static Future<void> configure() async {
    final prefs = await SharedPreferences.getInstance();
    final saved = prefs.getString(_prefsKey);
    if (saved != null && saved.trim().isNotEmpty) {
      _baseUrl = saved.trim();
    }
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

  Uri _uri(String path) => Uri.parse('$baseUrl/$path');

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
          return {
            'success': 1,
            'user': {
              'id': data['admin']['id'],
              'name': data['admin']['name'],
              'email': data['admin']['email'],
            }
          };
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

      if (response.statusCode == 200 &&
          response.body.trim().isNotEmpty) {
        final jsonData = jsonDecode(response.body);

        return {
          'success': jsonData['success'] ?? 0,
          'count': jsonData['count'] ?? 0,
          'data': jsonData['data'] ?? [], // ✅ FIXED KEY
          'message': jsonData['message'] ?? '',
        };
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
}
