import 'dart:convert';
import 'dart:io';

import 'package:http/http.dart' as http;

import 'api_service.dart';

class ReportService {
  static const String _fallbackBaseUrl = 'http://10.0.26.208';
  static const String _fallbackHostHeader = 'neovtrack.uitm.edu.my';

  Uri _uri(String path) => Uri.parse('${ApiService.baseUrl}/$path');

  Future<http.StreamedResponse> _sendMultipart(
    Future<http.MultipartRequest> Function(Uri url) buildRequest,
    Uri url,
  ) async {
    final request = await buildRequest(url);
    try {
      return await request.send();
    } on SocketException catch (e) {
      final isHostLookup = e.message.toLowerCase().contains('failed host lookup');
      final currentHost = request.url.host;
      final shouldRetry = isHostLookup &&
          currentHost.isNotEmpty &&
          currentHost != Uri.parse(_fallbackBaseUrl).host;

      if (!shouldRetry) {
        rethrow;
      }

      final fallbackUrl = Uri.parse(_fallbackBaseUrl).replace(
        path: request.url.path,
        queryParameters: request.url.hasQuery ? request.url.queryParameters : null,
      );

      final fallbackRequest = await buildRequest(fallbackUrl);
      fallbackRequest.headers['Host'] = _fallbackHostHeader;
      return await fallbackRequest.send();
    }
  }

  Future<Map<String, dynamic>> submitReport({
    required List<File> photos,
    required String plateNumber,
    required String offense,
    required double latitude,
    required double longitude,
    String ownerName = '',
    String idNumber = '',
    String phone = '',
    String vehicleType = '',
    String vehicleStatus = '',
    String sticker = '',
    int reporterId = 0,
    String reporterName = '',
    String reporterEmail = '',
    String reporterRole = 'user',
  }) async {
    Future<http.MultipartRequest> buildRequest(Uri url) async {
      final request = http.MultipartRequest('POST', url)
        ..fields['plate_number'] = plateNumber
        ..fields['offense_details'] = offense
        ..fields['latitude'] = latitude.toString()
        ..fields['longitude'] = longitude.toString()
        ..fields['owner_name'] = ownerName
        ..fields['id_number'] = idNumber
        ..fields['phone'] = phone
        ..fields['vehicle_type'] = vehicleType
        ..fields['vehicle_status'] = vehicleStatus
        ..fields['sticker'] = sticker
        ..fields['reporter_id'] = reporterId.toString()
        ..fields['reporter_name'] = reporterName
        ..fields['reporter_email'] = reporterEmail
        ..fields['reporter_role'] = reporterRole;

      for (final photo in photos) {
        request.files.add(
          await http.MultipartFile.fromPath(
            'photos[]',
            photo.path,
          ),
        );
      }

      return request;
    }

    final streamed = await _sendMultipart(buildRequest, _uri('report_vehicle_api.php'));
    final response = await http.Response.fromStream(streamed);

    if (response.statusCode != 200 || response.body.trim().isEmpty) {
      return {
        'success': 0,
        'message': 'Server error: ${response.statusCode}',
      };
    }

    return jsonDecode(response.body) as Map<String, dynamic>;
  }
}
