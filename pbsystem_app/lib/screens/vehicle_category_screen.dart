import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../widgets/nv_plate_chip.dart';
import '../widgets/sticker_badge.dart';
import '../widgets/web_app_bar.dart';
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
    setState(() { loading = true; message = ''; });
    final data = await api.searchCarUser(status: widget.status, showAll: widget.showAll);
    if (!mounted) return;
    setState(() {
      loading = false;
      if (data['success'] == 1) {
        results = data['data'] ?? [];
        if (results.isEmpty) message = 'No vehicles found.';
      } else {
        message = data['message'] ?? 'Failed to load';
      }
    });
  }

  (Color, Color) _statusPalette(String status) {
    final s = status.toLowerCase();
    if (s.contains('staf'))       return (AppColors.statStaffBorder, Colors.white);
    if (s.contains('pelajar'))    return (AppColors.statStudentBorder, Colors.white);
    if (s.contains('pelawat'))    return (AppColors.statVisitorBorder, Colors.white);
    if (s.contains('kontraktor')) return (AppColors.statContractorBorder, Colors.white);
    return (AppColors.primary, Colors.white);
  }

  Widget _statusBadge(String status) {
    final (bg, fg) = _statusPalette(status);
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(color: bg, borderRadius: BorderRadius.circular(20)),
      child: Text(status.toUpperCase(),
          style: TextStyle(color: fg, fontWeight: FontWeight.w700, fontSize: 11, letterSpacing: 0.4)),
    );
  }

  String _digitsOnly(String s) => s.replaceAll(RegExp(r'\D'), '');

  Future<void> _call(String phone) async {
    final p = _digitsOnly(phone);
    if (p.isEmpty) return;
    final uri = Uri.parse('tel:+$p');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  Future<void> _whatsapp(String phone) async {
    final p = _digitsOnly(phone);
    if (p.isEmpty) return;
    final wa = p.startsWith('0') ? '60${p.substring(1)}' : p;
    final uri = Uri.parse('https://wa.me/$wa');
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: WebAppBar(title: widget.title, subtitle: 'NEO V-TRACK'),
      body: loading
          ? const Center(child: CircularProgressIndicator())
          : RefreshIndicator(
              onRefresh: _loadData,
              child: ListView(
                padding: const EdgeInsets.all(12),
                children: [
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 6),
                    child: Row(
                      children: [
                        const FaIcon(FontAwesomeIcons.list, size: 14, color: AppColors.mutedText),
                        const SizedBox(width: 8),
                        Text('${results.length} record${results.length == 1 ? '' : 's'}',
                            style: const TextStyle(color: AppColors.mutedText, fontSize: 13, fontWeight: FontWeight.w600)),
                      ],
                    ),
                  ),
                  if (message.isNotEmpty)
                    Padding(
                      padding: const EdgeInsets.all(24),
                      child: Center(child: Text(message, style: const TextStyle(color: AppColors.mutedText))),
                    ),
                  ...results.map(_buildCard),
                ],
              ),
            ),
    );
  }

  Widget _buildCard(dynamic item) {
    final phone = (item['phone'] ?? '').toString().trim();
    final plate = (item['platenum'] ?? '-').toString().toUpperCase();
    final status = (item['status'] ?? '').toString();
    final owner = (item['name'] ?? '').toString();
    final type = (item['type'] ?? '').toString();
    final stickerStatus = (item['sticker'] ?? '').toString();
    final stickerNo = (item['stickerno'] ?? '').toString();

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(10),
        border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 6, offset: const Offset(0, 2))],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () => Navigator.of(context).push(
            MaterialPageRoute(builder: (_) => VehicleDetailScreen(vehicle: item)),
          ),
          child: Padding(
            padding: const EdgeInsets.all(14),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    NvPlateChip(plate),
                    _statusBadge(status),
                  ],
                ),
                const SizedBox(height: 6),
                Text(owner,
                    style: const TextStyle(color: AppColors.bodyText, fontWeight: FontWeight.w600, fontSize: 14)),
                const SizedBox(height: 6),
                Row(children: [
                  const FaIcon(FontAwesomeIcons.car, size: 11, color: AppColors.mutedText),
                  const SizedBox(width: 6),
                  Text(type.isEmpty ? '-' : type, style: const TextStyle(color: AppColors.mutedText, fontSize: 12)),
                  const SizedBox(width: 14),
                  StickerBadge(status: stickerStatus, stickerNo: stickerNo),
                ]),
                if (phone.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Row(children: [
                    Text(phone, style: const TextStyle(color: AppColors.bodyText, fontSize: 13)),
                    const Spacer(),
                    _miniBtn(FontAwesomeIcons.phone, AppColors.primary, () => _call(phone)),
                    const SizedBox(width: 6),
                    _miniBtn(FontAwesomeIcons.whatsapp, const Color(0xFF25D366), () => _whatsapp(phone)),
                  ]),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _miniBtn(IconData icon, Color color, VoidCallback onTap) => Material(
        color: color,
        shape: const CircleBorder(),
        child: InkWell(
          onTap: onTap,
          customBorder: const CircleBorder(),
          child: SizedBox(
            width: 32, height: 32,
            child: Center(child: FaIcon(icon, color: Colors.white, size: 13)),
          ),
        ),
      );
}
