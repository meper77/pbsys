// lib/services/api_service.dart
import 'dart:convert';
import 'package:http/http.dart' as http;

class ApiService {
  final String baseUrl = 'http://10.90.78.197/PBsystem/';

  /// ================= LOGIN =================
  Future<Map<String, dynamic>> login(
      String email, String password, String role) async {
    try {
      final endpoint =
          role == 'admin' ? 'login_admin_api.php' : 'login_user_api.php';

      final response = await http.post(
        Uri.parse('$baseUrl$endpoint'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
          'email': email,
          'password': password,
        },
      );

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
          'message': 'Server returned empty response'
        };
      }
    } catch (e) {
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
      final response = await http.post(
        Uri.parse('$baseUrl/register_user_api.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
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
      final response = await http.post(
        Uri.parse('$baseUrl/forgot_password_api.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
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
      final response = await http.post(
        Uri.parse('$baseUrl/search_car_user_api.php'),
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: {
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