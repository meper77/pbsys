import 'dart:convert';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';
import 'reports_detail_screen.dart';

class ReportsListScreen extends StatefulWidget {
  const ReportsListScreen({super.key});

  @override
  State<ReportsListScreen> createState() => _ReportsListScreenState();
}

class _ReportsListScreenState extends State<ReportsListScreen> {
  final ApiService _api = ApiService();
  late Future<List<Map<String, dynamic>>> _future;

  @override
  void initState() {
    super.initState();
    _future = _load();
  }

  Future<List<Map<String, dynamic>>> _load() async {
    final res = await _api.get('reports_list_api.php?limit=200');
    if (res.statusCode != 200) {
      throw Exception('HTTP ${res.statusCode}');
    }
    final body = jsonDecode(res.body);
    if (body['success'] != 1) {
      throw Exception(body['message'] ?? 'Failed to load reports');
    }
    final list = (body['data'] as List).cast<Map<String, dynamic>>();
    return list;
  }

  Future<void> _refresh() async {
    setState(() => _future = _load());
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: const WebAppBar(title: 'Vehicle Reports', subtitle: 'Submitted offense reports'),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<Map<String, dynamic>>>(
          future: _future,
          builder: (ctx, snap) {
            if (snap.connectionState != ConnectionState.done) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return ListView(children: [
                Padding(
                  padding: const EdgeInsets.all(24),
                  child: Text('Error: ${snap.error}',
                      style: const TextStyle(color: Colors.red)),
                ),
              ]);
            }
            final reports = snap.data ?? [];
            if (reports.isEmpty) {
              return ListView(children: const [
                Padding(
                  padding: EdgeInsets.all(40),
                  child: Center(child: Text('No reports yet.')),
                ),
              ]);
            }
            return ListView.separated(
              itemCount: reports.length,
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (ctx, i) {
                final r = reports[i];
                final photos = (r['photo_paths'] as List?) ?? [];
                return ListTile(
                  leading: CircleAvatar(
                    backgroundColor: AppColors.brandYellowTint,
                    child: const FaIcon(FontAwesomeIcons.flag, color: AppColors.brandYellowDeep, size: 14),
                  ),
                  title: Text(r['plate_number'] ?? '',
                      style: const TextStyle(fontFamily: 'JetBrainsMono', fontWeight: FontWeight.w700, color: AppColors.dark, letterSpacing: 1.0)),
                  subtitle: Text(
                    '${r['reporter_name'] ?? ''} · ${r['created_at'] ?? ''}\n${(r['offense_details'] ?? '').toString().split('\n').first}',
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                  ),
                  trailing: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.photo_library, size: 18),
                      Text('${photos.length}', style: const TextStyle(fontSize: 11)),
                    ],
                  ),
                  onTap: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ReportsDetailScreen(reportId: r['id']),
                      ),
                    );
                  },
                  isThreeLine: true,
                );
              },
            );
          },
        ),
      ),
    );
  }
}
