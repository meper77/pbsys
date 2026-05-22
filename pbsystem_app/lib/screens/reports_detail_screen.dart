import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';

class ReportsDetailScreen extends StatefulWidget {
  final dynamic reportId;
  const ReportsDetailScreen({super.key, required this.reportId});

  @override
  State<ReportsDetailScreen> createState() => _ReportsDetailScreenState();
}

class _ReportsDetailScreenState extends State<ReportsDetailScreen> {
  final ApiService _api = ApiService();
  late Future<Map<String, dynamic>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<Map<String, dynamic>> _load() async {
    final res = await _api.get('reports_view_api.php?id=${widget.reportId}');
    if (res.statusCode != 200) {
      throw Exception('HTTP ${res.statusCode}');
    }
    final body = jsonDecode(res.body);
    if (body['success'] != 1) {
      throw Exception(body['message'] ?? 'Failed to load report');
    }
    return Map<String, dynamic>.from(body['data']);
  }

  Future<void> _openMap(String lat, String lng) async {
    final uri = Uri.parse('https://www.google.com/maps?q=$lat,$lng');
    if (!await launchUrl(uri, mode: LaunchMode.externalApplication)) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Could not open Maps')),
      );
    }
  }

  Widget _kv(String k, String? v) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 6),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 130,
              child: Text(k, style: TextStyle(color: Colors.grey.shade600)),
            ),
            Expanded(
              child: Text(v?.isNotEmpty == true ? v! : '-',
                  style: const TextStyle(fontWeight: FontWeight.w500)),
            ),
          ],
        ),
      );

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: WebAppBar(title: 'Report #${widget.reportId}', subtitle: 'Detail view'),
      body: FutureBuilder<Map<String, dynamic>>(
        future: _future,
        builder: (ctx, snap) {
          if (snap.connectionState != ConnectionState.done) {
            return const Center(child: CircularProgressIndicator());
          }
          if (snap.hasError) {
            return Padding(
              padding: const EdgeInsets.all(24),
              child: Text('Error: ${snap.error}',
                  style: const TextStyle(color: Colors.red)),
            );
          }
          final r = snap.data!;
          final photos =
              ((r['photo_paths'] as List?) ?? []).cast<dynamic>().map((e) => e.toString()).toList();
          return SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                _kv('Submitted',  r['created_at']?.toString()),
                _kv('Plate',      r['plate_number']?.toString()),
                _kv('Reporter',
                    '${r['reporter_name'] ?? ''} (${r['reporter_role'] ?? ''})'),
                _kv('Reporter Email', r['reporter_email']?.toString()),
                _kv('Owner',      r['owner_name']?.toString()),
                _kv('Phone',      r['phone']?.toString()),
                _kv('Vehicle',
                    '${r['vehicle_type'] ?? ''}  ${r['vehicle_status'] ?? ''}'),
                _kv('Sticker',    r['sticker']?.toString()),
                const SizedBox(height: 12),
                Text('Offense Details',
                    style: Theme.of(context).textTheme.titleSmall),
                Container(
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(top: 6),
                  decoration: BoxDecoration(
                    color: Colors.grey.shade100,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(r['offense_details']?.toString() ?? '-'),
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    const FaIcon(FontAwesomeIcons.locationDot, color: AppColors.brandPurple, size: 16),
                    const SizedBox(width: 6),
                    Expanded(
                      child: Text(
                        '${r['latitude']}, ${r['longitude']}',
                        style: const TextStyle(fontWeight: FontWeight.w500),
                      ),
                    ),
                    TextButton.icon(
                      onPressed: () => _openMap(
                          r['latitude'].toString(), r['longitude'].toString()),
                      icon: const Icon(Icons.open_in_new, size: 16),
                      label: const Text('Open in Maps'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Text('Photos (${photos.length})',
                    style: Theme.of(context).textTheme.titleSmall),
                const SizedBox(height: 8),
                if (photos.isEmpty)
                  const Padding(
                    padding: EdgeInsets.symmetric(vertical: 12),
                    child: Text('No photos attached.',
                        style: TextStyle(color: Colors.grey)),
                  )
                else
                  Wrap(
                    spacing: 8,
                    runSpacing: 8,
                    children: photos.map((p) {
                      final url = p.startsWith('http')
                          ? p
                          : '${ApiService.baseUrl}$p';
                      return GestureDetector(
                        onTap: () => launchUrl(Uri.parse(url),
                            mode: LaunchMode.externalApplication),
                        child: ClipRRect(
                          borderRadius: BorderRadius.circular(8),
                          child: Image.network(
                            url,
                            width: 110,
                            height: 110,
                            fit: BoxFit.cover,
                            errorBuilder: (_, __, ___) => Container(
                              width: 110,
                              height: 110,
                              color: Colors.grey.shade300,
                              child: const Icon(Icons.broken_image),
                            ),
                          ),
                        ),
                      );
                    }).toList(),
                  ),
                const SizedBox(height: 24),
              ],
            ),
          );
        },
      ),
    );
  }
}
