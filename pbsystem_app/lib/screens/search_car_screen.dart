import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:url_launcher/url_launcher.dart';

import '../services/api_service.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import '../widgets/sticker_badge.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_gradient_button.dart';
import 'vehicle_detail_screen.dart';

class SearchCarScreen extends StatefulWidget {
  const SearchCarScreen({super.key});
  @override
  State<SearchCarScreen> createState() => _SearchCarScreenState();
}

class _SearchCarScreenState extends State<SearchCarScreen> {
  final searchCtl = TextEditingController();
  final api = ApiService();
  bool loading = false;
  String message = '';
  List<dynamic> results = [];
  String activeStatus = '';
  bool showAll = false;

  Future<void> _performSearch({
    String search = '',
    String status = '',
    bool showAllRecords = false,
    bool requireQuery = false,
  }) async {
    final q = search.trim();
    if (requireQuery && q.isEmpty) {
      setState(() {
        message = 'Please enter plate number, owner name, or ID.';
        results = [];
        activeStatus = '';
        showAll = false;
      });
      return;
    }
    setState(() {
      loading = true;
      message = '';
      results = [];
      activeStatus = status;
      showAll = showAllRecords;
    });
    final data = await api.searchCarUser(search: q, status: status, showAll: showAllRecords);
    if (!mounted) return;
    setState(() {
      loading = false;
      if (data['success'] == 1) {
        results = data['data'] ?? [];
        if (results.isEmpty) message = 'No vehicles found.';
      } else {
        message = data['message'] ?? 'Search failed';
      }
    });
  }

  (List<Color>, Color) _statusPalette(String status) {
    final s = status.toLowerCase();
    if (s.contains('staf'))       return (AppColors.staffGradient,      AppColors.statStaffBorder);
    if (s.contains('pelajar'))    return (AppColors.studentGradient,    AppColors.statStudentBorder);
    if (s.contains('pelawat'))    return (AppColors.visitorGradient,    AppColors.statVisitorBorder);
    if (s.contains('kontraktor')) return (AppColors.contractorGradient, AppColors.statContractorBorder);
    return (AppColors.heroGradient, AppColors.primary);
  }

  Widget _filterChip({required String label, required IconData icon, required bool selected, required List<Color> gradient, required VoidCallback onTap}) {
    // Yellow gradients need dark text for legibility; others stay white.
    final yellowBg = gradient.isNotEmpty && gradient.first == AppColors.brandYellow;
    final fg = selected ? (yellowBg ? AppColors.dark : Colors.white) : AppColors.bodyText;
    return Padding(
      padding: const EdgeInsets.only(right: 8, bottom: 8),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          onTap: loading ? null : onTap,
          borderRadius: BorderRadius.circular(20),
          child: Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              gradient: selected ? LinearGradient(colors: gradient) : null,
              color: selected ? null : Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: selected ? Colors.transparent : AppColors.cardBorder),
            ),
            child: Row(mainAxisSize: MainAxisSize.min, children: [
              FaIcon(icon, size: 11, color: fg),
              const SizedBox(width: 6),
              Text(label, style: TextStyle(
                color: fg, fontSize: 12, fontWeight: FontWeight.w600,
              )),
            ]),
          ),
        ),
      ),
    );
  }

  String _digits(String s) => s.replaceAll(RegExp(r'\D'), '');
  Future<void> _call(String phone) async {
    final p = _digits(phone);
    if (p.isEmpty) return;
    final uri = Uri.parse('tel:+$p');
    if (await canLaunchUrl(uri)) await launchUrl(uri);
  }

  Widget _statusBadge(String status) {
    final (grad, _) = _statusPalette(status);
    final yellowBg = grad.isNotEmpty && grad.first == AppColors.brandYellow;
    final fg = yellowBg ? AppColors.dark : Colors.white;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        gradient: LinearGradient(colors: grad),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(status.toUpperCase(),
          style: TextStyle(color: fg, fontSize: 11, fontWeight: FontWeight.w700, letterSpacing: 0.4)),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: const WebAppBar(title: 'Search Vehicles', subtitle: 'NEO V-TRACK'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            // Search bar
            TextField(
              controller: searchCtl,
              textCapitalization: TextCapitalization.characters,
              decoration: const InputDecoration(
                hintText: 'Plate number / owner / ID',
                prefixIcon: Padding(
                  padding: EdgeInsets.symmetric(horizontal: 14),
                  child: FaIcon(FontAwesomeIcons.magnifyingGlass, size: 16, color: AppColors.primary),
                ),
                prefixIconConstraints: BoxConstraints(minWidth: 44),
              ),
              onSubmitted: (_) => _performSearch(search: searchCtl.text, requireQuery: true),
            ),
            const SizedBox(height: 12),
            WebGradientButton(
              label: loading ? 'SEARCHING…' : 'SEARCH',
              icon: FontAwesomeIcons.magnifyingGlass,
              loading: loading,
              onPressed: loading ? null : () => _performSearch(search: searchCtl.text, requireQuery: true),
            ),
            const SizedBox(height: 14),

            // Filters
            Wrap(children: [
              _filterChip(label: 'Staff', icon: FontAwesomeIcons.briefcase, selected: activeStatus == 'Staf' && !showAll,
                  gradient: AppColors.staffGradient, onTap: () => _performSearch(status: 'Staf')),
              _filterChip(label: 'Student', icon: FontAwesomeIcons.graduationCap, selected: activeStatus == 'Pelajar' && !showAll,
                  gradient: AppColors.studentGradient, onTap: () => _performSearch(status: 'Pelajar')),
              _filterChip(label: 'Visitor', icon: FontAwesomeIcons.userClock, selected: activeStatus == 'Pelawat' && !showAll,
                  gradient: AppColors.visitorGradient, onTap: () => _performSearch(status: 'Pelawat')),
              _filterChip(label: 'Contractor', icon: FontAwesomeIcons.helmetSafety, selected: activeStatus == 'Kontraktor' && !showAll,
                  gradient: AppColors.contractorGradient, onTap: () => _performSearch(status: 'Kontraktor')),
              _filterChip(label: 'All', icon: FontAwesomeIcons.gripVertical, selected: showAll,
                  gradient: AppColors.totalGradient, onTap: () => _performSearch(showAllRecords: true)),
            ]),

            const SizedBox(height: 10),
            if (message.isNotEmpty)
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 12),
                child: Text(message, style: const TextStyle(color: AppColors.mutedText), textAlign: TextAlign.center),
              ),
            if (results.isNotEmpty)
              Align(
                alignment: Alignment.centerLeft,
                child: Padding(
                  padding: const EdgeInsets.only(top: 4, bottom: 6),
                  child: Text('${results.length} result${results.length == 1 ? '' : 's'}',
                      style: const TextStyle(color: AppColors.mutedText, fontWeight: FontWeight.w600)),
                ),
              ),

            ...results.map(_buildResultCard),

            if (results.isNotEmpty || message.isNotEmpty) ...[
              const SizedBox(height: 8),
              OutlinedButton.icon(
                onPressed: () => setState(() {
                  searchCtl.clear();
                  activeStatus = '';
                  showAll = false;
                  results = [];
                  message = '';
                }),
                icon: const FaIcon(FontAwesomeIcons.arrowRotateLeft, size: 12),
                label: const Text('Reset Search'),
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _buildResultCard(dynamic item) {
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
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 5),
                      decoration: BoxDecoration(
                        color: AppColors.brandYellow,
                        border: Border.all(color: AppColors.dark, width: 1.75),
                        borderRadius: BorderRadius.circular(5),
                      ),
                      child: Text(
                        plate,
                        style: AppTypography.plateMono(size: 18, color: AppColors.dark)
                            .copyWith(letterSpacing: 1.4),
                      ),
                    ),
                    _statusBadge(status),
                  ],
                ),
                const SizedBox(height: 6),
                Text(owner.toUpperCase(),
                    style: const TextStyle(color: AppColors.bodyText, fontWeight: FontWeight.w600, fontSize: 13)),
                const SizedBox(height: 4),
                Row(children: [
                  const FaIcon(FontAwesomeIcons.car, size: 11, color: AppColors.mutedText),
                  const SizedBox(width: 6),
                  Text(type.isEmpty ? '-' : type, style: const TextStyle(color: AppColors.mutedText, fontSize: 12)),
                  const SizedBox(width: 14),
                  StickerBadge(status: stickerStatus, stickerNo: stickerNo),
                ]),
                if (phone.isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Row(children: [
                    Expanded(child: Text(phone, style: const TextStyle(color: AppColors.bodyText, fontSize: 13))),
                    OutlinedButton.icon(
                      onPressed: () => _call(phone),
                      icon: const FaIcon(FontAwesomeIcons.phone, size: 11),
                      label: const Text('Call'),
                    ),
                  ]),
                ],
              ],
            ),
          ),
        ),
      ),
    );
  }
}
