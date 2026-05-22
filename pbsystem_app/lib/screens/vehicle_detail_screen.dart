import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import '../widgets/nv_plate_chip.dart';
import '../widgets/sticker_badge.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_gradient_button.dart';
import '../widgets/web_section_title.dart';

class VehicleDetailScreen extends StatelessWidget {
  const VehicleDetailScreen({super.key, required this.vehicle});

  final Map<String, dynamic> vehicle;

  String _digits(String s) => s.replaceAll(RegExp(r'\D'), '');

  Future<void> _call(String phone) async {
    final p = _digits(phone);
    if (p.isEmpty) return;
    final uri = Uri.parse('tel:+$p');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  Future<void> _whatsapp(String phone) async {
    final p = _digits(phone);
    if (p.isEmpty) return;
    final wa = p.startsWith('0') ? '60${p.substring(1)}' : p;
    final uri = Uri.parse('https://wa.me/$wa');
    if (await canLaunchUrl(uri)) await launchUrl(uri, mode: LaunchMode.externalApplication);
  }

  @override
  Widget build(BuildContext context) {
    final plate    = (vehicle['platenum']  ?? '-').toString().toUpperCase();
    final owner    = (vehicle['name']      ?? '-').toString();
    final idnumber = (vehicle['idnumber']  ?? '-').toString();
    final phone    = (vehicle['phone']     ?? '').toString();
    final type     = (vehicle['type']      ?? '-').toString();
    final status   = (vehicle['status']    ?? '-').toString();
    final sticker  = (vehicle['sticker']   ?? '').toString();
    final stickerNo = (vehicle['stickerno'] ?? '').toString();

    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: WebAppBar(title: 'Vehicle Detail', subtitle: plate),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Plate hero
          Container(
            padding: const EdgeInsets.all(24),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: AppColors.heroGradient,
                begin: Alignment.topLeft, end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(
              children: [
                Text(
                  'PLATE',
                  style: AppTypography.eyebrow(color: AppColors.brandYellow),
                ),
                const SizedBox(height: 12),
                NvPlateChip(plate, size: NvPlateSize.large),
                const SizedBox(height: 12),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.white.withValues(alpha: 0.14),
                    border: Border.all(color: Colors.white.withValues(alpha: 0.22)),
                    borderRadius: BorderRadius.circular(20),
                  ),
                  child: Text(status.toUpperCase(),
                      style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 11, letterSpacing: 1.2)),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Info card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(10),
              border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const WebSectionTitle(title: 'Vehicle Information', icon: FontAwesomeIcons.circleInfo),
                _kv(FontAwesomeIcons.user,        'Owner',     owner),
                _kv(FontAwesomeIcons.idCard,      'ID Number', idnumber),
                _kv(FontAwesomeIcons.car,         'Type',      type),
                _kv(FontAwesomeIcons.userTag,     'Status',    status),
                _kvWidget(FontAwesomeIcons.idBadge, 'Sticker', StickerBadge(status: sticker, stickerNo: stickerNo)),
              ],
            ),
          ),

          if (phone.trim().isNotEmpty) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const WebSectionTitle(title: 'Contact Owner', icon: FontAwesomeIcons.phone),
                  Row(children: [
                    Expanded(child: Text(phone, style: const TextStyle(color: AppColors.bodyText, fontSize: 15))),
                    OutlinedButton.icon(
                      onPressed: () => _call(phone),
                      icon: const FaIcon(FontAwesomeIcons.phone, size: 12),
                      label: const Text('Call'),
                    ),
                    const SizedBox(width: 8),
                    ElevatedButton.icon(
                      onPressed: () => _whatsapp(phone),
                      style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF25D366)),
                      icon: const FaIcon(FontAwesomeIcons.whatsapp, size: 12, color: Colors.white),
                      label: const Text('WhatsApp', style: TextStyle(color: Colors.white)),
                    ),
                  ]),
                ],
              ),
            ),
          ],

          const SizedBox(height: 16),
          WebGradientButton(
            label: 'REPORT OFFENSE',
            icon: FontAwesomeIcons.flag,
            gradient: const [AppColors.danger, AppColors.brandYellowDeep],
            onPressed: () => Navigator.pushNamed(context, '/report_vehicle', arguments: {
              'vehicle': vehicle,
              'reporterId': ApiService.currentUserId,
              'reporterName': ApiService.currentUserName,
              'reporterEmail': ApiService.currentUserEmail,
              'reporterRole': ApiService.currentUserRole,
            }),
          ),
        ],
      ),
    );
  }

  Widget _kv(IconData icon, String label, String value) => _kvWidget(
        icon, label, Text(value.isEmpty ? '-' : value,
            style: const TextStyle(color: AppColors.bodyText, fontWeight: FontWeight.w600)),
      );

  Widget _kvWidget(IconData icon, String label, Widget value) => Padding(
        padding: const EdgeInsets.symmetric(vertical: 8),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.center,
          children: [
            FaIcon(icon, size: 13, color: AppColors.primary),
            const SizedBox(width: 10),
            SizedBox(width: 100,
              child: Text(label, style: const TextStyle(color: AppColors.mutedText, fontSize: 13)),
            ),
            Expanded(child: value),
          ],
        ),
      );
}
