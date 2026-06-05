import 'dart:convert';
import 'dart:io';
import 'package:http/http.dart' as http;
import '../config.dart';
import '../models.dart';

class ApiException implements Exception {
  final String message;
  ApiException(this.message);
  @override
  String toString() => message;
}

/// Thin client over the NEO V-TRACK `/api/*` JSON endpoints (Hestia).
class Api {
  static const Duration _timeout = Duration(seconds: 20);

  static Future<Map<String, dynamic>> _decode(http.Response r) async {
    if (r.statusCode >= 500) throw ApiException('Server error (${r.statusCode})');
    try {
      final body = json.decode(r.body);
      if (body is Map<String, dynamic>) return body;
      throw ApiException('Unexpected response');
    } on FormatException {
      throw ApiException('Invalid server response');
    }
  }

  /// Login as admin or user. Returns the authenticated [AppUser].
  static Future<AppUser> login(String email, String password, String role) async {
    final endpoint = role == 'admin' ? 'login_admin_api.php' : 'login_user_api.php';
    final http.Response r;
    try {
      r = await http
          .post(Config.api(endpoint),
              headers: {'Content-Type': 'application/json'},
              body: json.encode({'email': email, 'password': password}))
          .timeout(_timeout);
    } on SocketException {
      throw ApiException('Cannot reach server. Check your network.');
    }
    final j = await _decode(r);
    final success = j['success'] == 1 || j['success'] == true;
    if (!success) throw ApiException((j['message'] ?? 'Invalid email or password').toString());
    final userJson = (j['user'] ?? j['admin'] ?? {}) as Map<String, dynamic>;
    if (userJson.isEmpty) {
      return AppUser(id: 0, name: email.split('@').first, email: email, role: role);
    }
    return AppUser.fromJson(userJson, role);
  }

  /// Dashboard counts.
  static Future<Stats> stats() async {
    final r = await http.get(Config.api('vehicle_stats_api.php', {'action': 'get_stats'})).timeout(_timeout);
    final j = await _decode(r);
    if (j['success'] != true) throw ApiException('Failed to load stats');
    return Stats.fromJson((j['stats'] ?? {}) as Map<String, dynamic>);
  }

  /// Vehicles of a category (staff|student|visitor|contractor).
  static Future<List<Vehicle>> vehiclesByType(String type, {int limit = 100}) async {
    final r = await http
        .get(Config.api('vehicle_stats_api.php',
            {'action': 'get_vehicles_by_type', 'type': type, 'limit': '$limit'}))
        .timeout(_timeout);
    final j = await _decode(r);
    final list = (j['vehicles'] ?? []) as List;
    return list.map((e) => Vehicle.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Plate/name/ID search across all categories.
  static Future<List<Vehicle>> search(String query) async {
    if (query.trim().length < 2) return [];
    final r = await http
        .get(Config.api('vehicle_search_api.php', {'action': 'search', 'q': query.trim()}))
        .timeout(_timeout);
    final j = await _decode(r);
    final list = (j['data'] ?? []) as List;
    return list.map((e) => Vehicle.fromJson(e as Map<String, dynamic>)).toList();
  }

  /// Submit an offense report with photos and GPS. Returns the new report id.
  static Future<int> submitReport({
    required Vehicle? vehicle,
    required String plate,
    required String offense,
    required double latitude,
    required double longitude,
    required List<File> photos,
    required AppUser reporter,
  }) async {
    final req = http.MultipartRequest('POST', Config.api('report_vehicle_api.php'));
    req.fields.addAll({
      'plate_number': plate,
      'offense_details': offense,
      'owner_name': vehicle?.name ?? '',
      'id_number': vehicle?.idnumber ?? '',
      'phone': vehicle?.phone ?? '',
      'vehicle_type': vehicle?.type ?? '',
      'vehicle_status': vehicle?.status ?? '',
      'reporter_name': reporter.name,
      'reporter_email': reporter.email,
      'reporter_role': reporter.role,
      'reporter_id': '${reporter.id}',
      'latitude': '$latitude',
      'longitude': '$longitude',
    });
    for (final p in photos) {
      req.files.add(await http.MultipartFile.fromPath('photos[]', p.path));
    }
    final streamed = await req.send().timeout(const Duration(seconds: 40));
    final r = await http.Response.fromStream(streamed);
    final j = await _decode(r);
    final success = j['success'] == 1 || j['success'] == true;
    if (!success) throw ApiException((j['message'] ?? 'Failed to submit report').toString());
    return (j['report_id'] is int) ? j['report_id'] : int.tryParse('${j['report_id']}') ?? 0;
  }
}
