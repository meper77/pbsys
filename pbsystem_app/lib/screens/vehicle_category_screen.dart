import 'package:flutter/material.dart';
import 'package:url_launcher/url_launcher.dart';
import '../services/api_service.dart';
import 'vehicle_detail_screen.dart';

class VehicleCategoryScreen extends StatefulWidget {
  const VehicleCategoryScreen({
    super.key,
    required this.title,
    this.status = '',
    this.showAll = false,
  });

  final String title;
  final String status;
  final bool showAll;

  @override
  State<VehicleCategoryScreen> createState() => _VehicleCategoryScreenState();
}

class _VehicleCategoryScreenState extends State<VehicleCategoryScreen> {
  static const primaryColor = Color(0xFF4B2E83);
  static const cardBackground = Color(0xFFF6F2FC);
  final ApiService api = ApiService();
  bool loading = true;
  String message = '';
  List<dynamic> results = [];

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  Future<void> _loadData() async {
    setState(() {
      loading = true;
      message = '';
    });

    final data = await api.searchCarUser(
      status: widget.status,
      showAll: widget.showAll,
    );

    setState(() {
      loading = false;
      if (data['success'] == 1) {
        results = data['data'] ?? [];
        if (results.isEmpty) {
          message = 'NO VEHICLES FOUND';
        }
      } else {
        message = (data['message'] ?? 'FAILED TO LOAD').toUpperCase();
      }
    });
  }

  Color _statusColor(String status) {
    final normalized = status.toLowerCase();
    if (normalized.contains('staf') || normalized.contains('staff')) {
      return const Color(0xFF1976D2);
    }
    if (normalized.contains('pelajar') || normalized.contains('student')) {
      return const Color(0xFF7B1FA2);
    }
    if (normalized.contains('pelawat') || normalized.contains('visitor')) {
      return const Color(0xFF388E3C);
    }
    if (normalized.contains('kontraktor') || normalized.contains('contractor')) {
      return const Color(0xFFF57C00);
    }
    return primaryColor;
  }

  Widget _statusBadge(String status) {
    final color = _statusColor(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(6),
        border: Border.all(color: color.withOpacity(0.5)),
      ),
      child: Text(
        status.toUpperCase(),
        style: TextStyle(
          color: color,
          fontWeight: FontWeight.w700,
          fontSize: 12,
        ),
      ),
    );
  }

  Widget _rowLabel(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(top: 4),
      child: RichText(
        text: TextSpan(
          style: const TextStyle(color: Colors.black87, fontSize: 14),
          children: [
            TextSpan(
              text: '$label: ',
              style: const TextStyle(fontWeight: FontWeight.w700),
            ),
            TextSpan(text: value.isEmpty ? '-' : value),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text(widget.title),
      ),
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF4B2E83), Color(0xFF6A1B9A)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: loading
            ? const Center(child: CircularProgressIndicator(color: Colors.white))
            : RefreshIndicator(
                onRefresh: _loadData,
                child: ListView(
                  padding: const EdgeInsets.all(16),
                  children: [
                    if (message.isNotEmpty)
                      Text(
                        message,
                        textAlign: TextAlign.center,
                        style: const TextStyle(
                          color: Colors.white,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
                    if (results.isNotEmpty)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 10),
                        child: Text(
                          '${results.length} RECORDS',
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ...results.map((item) {
                      final phone = (item['phone'] ?? '').toString().trim();
                      final sticker = (item['sticker'] ?? '').toString().trim();
                      return Card(
                        margin: const EdgeInsets.only(bottom: 12),
                        color: cardBackground,
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Padding(
                          padding: const EdgeInsets.all(16),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Row(
                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                children: [
                                  Text(
                                    (item['platenum'] ?? '-')
                                        .toString()
                                        .toUpperCase(),
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      color: primaryColor,
                                      fontSize: 18,
                                    ),
                                  ),
                                  _statusBadge((item['status'] ?? '-').toString()),
                                ],
                              ),
                              _rowLabel(
                                'OWNER',
                                (item['name'] ?? '').toString().toUpperCase(),
                              ),
                              _rowLabel(
                                'ID NUMBER',
                                (item['idnumber'] ?? '').toString().toUpperCase(),
                              ),
                              Row(
                                children: [
                                  const Text('PHONE: '),
                                  GestureDetector(
                                    onTap: () async {
                                      if (phone.isEmpty) {
                                        return;
                                      }
                                      final uri = Uri.parse('tel:$phone');
                                      if (await canLaunchUrl(uri)) {
                                        await launchUrl(uri);
                                      }
                                    },
                                    child: Text(
                                      phone.isEmpty ? '-' : phone,
                                      style: const TextStyle(
                                        color: Colors.blue,
                                        decoration: TextDecoration.underline,
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              _rowLabel(
                                'TYPE',
                                (item['type'] ?? '').toString().toUpperCase(),
                              ),
                              _rowLabel(
                                'STICKER',
                                sticker.isEmpty ? '-' : sticker.toUpperCase(),
                              ),
                              const SizedBox(height: 8),
                              SizedBox(
                                width: double.infinity,
                                child: OutlinedButton(
                                  onPressed: () {
                                    Navigator.of(context).push(
                                      MaterialPageRoute(builder: (_) => VehicleDetailScreen(vehicle: item)),
                                    );
                                  },
                                  style: OutlinedButton.styleFrom(
                                    foregroundColor: primaryColor,
                                  ),
                                  child: const Text('VIEW DETAILS'),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                ),
              ),
      ),
    );
  }
}
