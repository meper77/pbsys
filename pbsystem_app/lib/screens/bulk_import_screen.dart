import 'dart:convert';
import 'dart:io';
import 'package:file_picker/file_picker.dart';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:http/http.dart' as http;
import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_gradient_button.dart';

class BulkImportScreen extends StatefulWidget {
  const BulkImportScreen({super.key});

  @override
  State<BulkImportScreen> createState() => _BulkImportScreenState();
}

class _BulkImportScreenState extends State<BulkImportScreen> {
  File? _file;
  bool _uploading = false;
  int? _inserted;
  int? _skipped;
  List<dynamic> _errors = [];
  String? _errorMsg;

  Future<void> _pick() async {
    final r = await FilePicker.platform.pickFiles(
      type: FileType.custom,
      allowedExtensions: ['csv'],
    );
    if (r != null && r.files.single.path != null) {
      setState(() {
        _file = File(r.files.single.path!);
        _inserted = _skipped = null;
        _errors = [];
        _errorMsg = null;
      });
    }
  }

  Future<void> _upload() async {
    final f = _file;
    if (f == null) return;
    setState(() {
      _uploading = true;
      _errorMsg = null;
    });
    try {
      final uri = Uri.parse('${ApiService.baseUrl}/api/bulk_import_api.php');
      final req = http.MultipartRequest('POST', uri);
      req.files.add(await http.MultipartFile.fromPath('csv_file', f.path));
      if (ApiService.webSessionCookie.isNotEmpty) {
        req.headers['Cookie'] = ApiService.webSessionCookie;
      }
      final streamed = await req.send();
      final res = await http.Response.fromStream(streamed);
      final body = jsonDecode(res.body);
      if (res.statusCode >= 400 || body['success'] != 1) {
        throw Exception(body['message'] ?? 'HTTP ${res.statusCode}');
      }
      setState(() {
        _inserted = body['inserted'] ?? 0;
        _skipped  = body['skipped']  ?? 0;
        _errors   = (body['errors'] as List?) ?? [];
      });
    } catch (e) {
      setState(() => _errorMsg = e.toString());
    } finally {
      if (mounted) setState(() => _uploading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: const WebAppBar(title: 'Bulk Import', subtitle: 'CSV upload'),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'CSV format: name, phone, idnumber, type, status, platenum, [sticker], [stickerno].\n'
              'Header row is auto-skipped if it contains "name" or "platenum".',
              style: TextStyle(color: Colors.grey),
            ),
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
              ),
              child: Row(
                children: [
                  const FaIcon(FontAwesomeIcons.fileCsv, color: AppColors.primary, size: 18),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Text(
                      _file == null
                          ? 'No file chosen.'
                          : _file!.path.split(Platform.pathSeparator).last,
                      style: const TextStyle(color: AppColors.bodyText),
                    ),
                  ),
                  TextButton.icon(
                    onPressed: _uploading ? null : _pick,
                    icon: const FaIcon(FontAwesomeIcons.folderOpen, size: 12),
                    label: const Text('Choose CSV'),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 12),
            WebGradientButton(
              label: _uploading ? 'Uploading…' : 'Upload & Import',
              icon: FontAwesomeIcons.fileArrowUp,
              loading: _uploading,
              onPressed: _file == null || _uploading ? null : _upload,
            ),
            const SizedBox(height: 16),
            if (_errorMsg != null)
              Card(
                color: Colors.red.shade50,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Text('Error: $_errorMsg',
                      style: const TextStyle(color: Colors.red)),
                ),
              ),
            if (_inserted != null)
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.check_circle, color: Colors.green),
                          const SizedBox(width: 8),
                          Text('Inserted: $_inserted',
                              style: const TextStyle(fontWeight: FontWeight.bold)),
                          const SizedBox(width: 18),
                          const Icon(Icons.cancel, color: Colors.orange),
                          const SizedBox(width: 8),
                          Text('Skipped: $_skipped'),
                        ],
                      ),
                      if (_errors.isNotEmpty) ...[
                        const Divider(),
                        const Text('Row errors:',
                            style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 6),
                        SizedBox(
                          height: 240,
                          child: ListView(
                            children: _errors.map((e) {
                              final row = e is Map ? e['row'] : '?';
                              final msg = e is Map ? e['message'] : e.toString();
                              return Padding(
                                padding: const EdgeInsets.symmetric(vertical: 4),
                                child: Text('• row $row: $msg',
                                    style: const TextStyle(fontSize: 12)),
                              );
                            }).toList(),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
