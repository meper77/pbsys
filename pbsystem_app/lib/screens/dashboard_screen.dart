import 'dart:async';
import 'dart:convert';
import 'dart:io';
import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';

import '../services/api_service.dart';
import '../services/profile_image_service.dart';
import '../theme/app_colors.dart';
import '../theme/app_typography.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_section_title.dart';
import 'account_management_screen.dart';
import 'admin_panel_screen.dart';
import 'bulk_import_screen.dart';
import 'reports_list_screen.dart';

class DashboardScreen extends StatefulWidget {
  final int userId;
  final String name;
  final String email;
  final String role;

  const DashboardScreen({
    super.key,
    required this.userId,
    required this.name,
    required this.email,
    this.role = 'user',
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final ApiService _api = ApiService();
  Timer? _clockTimer;
  String currentTime = '';
  String currentDate = '';
  File? profileImage;

  // Live stats
  int? _staffCount, _studentCount, _visitorCount, _contractorCount, _totalVehicles, _totalUsers;
  bool _statsLoading = true;

  @override
  void initState() {
    super.initState();
    _tickClock();
    _clockTimer = Timer.periodic(const Duration(seconds: 1), (_) => _tickClock());
    _loadProfileImage();
    _loadStats();
  }

  @override
  void dispose() {
    _clockTimer?.cancel();
    super.dispose();
  }

  void _tickClock() {
    final now = DateTime.now();
    final t = '${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}:${now.second.toString().padLeft(2, '0')}';
    final d = '${now.day.toString().padLeft(2, '0')}-${now.month.toString().padLeft(2, '0')}-${now.year}';
    if (!mounted) return;
    setState(() { currentTime = t; currentDate = d; });
  }

  Future<void> _loadProfileImage() async {
    final img = await ProfileImageService.loadProfileImage(widget.userId);
    if (!mounted) return;
    setState(() => profileImage = img);
  }

  Future<void> _loadStats() async {
    try {
      final res = await _api.get('vehicle_stats_api.php?action=get_stats');
      if (res.statusCode != 200) throw Exception('HTTP ${res.statusCode}');
      final body = jsonDecode(res.body);
      if (body['success'] != true) throw Exception(body['message'] ?? 'Stats failed');
      final s = Map<String, dynamic>.from(body['stats'] ?? {});
      if (!mounted) return;
      setState(() {
        _staffCount      = (s['staff']      ?? 0) as int;
        _studentCount    = (s['student']    ?? 0) as int;
        _visitorCount    = (s['visitor']    ?? 0) as int;
        _contractorCount = (s['contractor'] ?? 0) as int;
        _totalVehicles   = (s['total']      ?? 0) as int;
        _totalUsers      = (s['total_users'] ?? 0) as int;
        _statsLoading = false;
      });
    } catch (_) {
      if (!mounted) return;
      setState(() => _statsLoading = false);
    }
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(child: const Text('Cancel'), onPressed: () => Navigator.pop(context)),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            child: const Text('Logout'),
            onPressed: () {
              Navigator.pop(context);
              Navigator.popUntil(context, (r) => r.isFirst);
              Navigator.pushReplacementNamed(context, '/welcome');
            },
          ),
        ],
      ),
    );
  }

  void _openCategory(String title, {String status = '', bool showAll = false}) {
    Navigator.pushNamed(context, '/vehicle_category', arguments: {
      'title': title, 'status': status, 'showAll': showAll,
    });
  }

  String _pct(int? n) {
    if (n == null || _totalVehicles == null || _totalVehicles == 0) return '—';
    final p = (n / _totalVehicles!) * 100;
    return '${p.toStringAsFixed(1)}%';
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = widget.role == 'admin';
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: WebAppBar(
        title: 'NEO V-TRACK',
        subtitle: isAdmin ? 'Administrator' : 'User Dashboard',
        actions: [
          IconButton(
            icon: const FaIcon(FontAwesomeIcons.circleUser, size: 18, color: Colors.white),
            onPressed: () => Navigator.pushNamed(context, '/profile', arguments: {
              'userId': widget.userId, 'name': widget.name, 'email': widget.email,
            }).then((_) => _loadProfileImage()),
          ),
          IconButton(
            icon: const FaIcon(FontAwesomeIcons.rightFromBracket, size: 18, color: Colors.white),
            onPressed: _confirmLogout,
          ),
        ],
        tabs: [
          WebNavTab(icon: FontAwesomeIcons.house,        label: 'Dashboard', onTap: () {}, active: true),
          WebNavTab(icon: FontAwesomeIcons.magnifyingGlass, label: 'Search',
              onTap: () => Navigator.pushNamed(context, '/search_car')),
          WebNavTab(icon: FontAwesomeIcons.briefcase,    label: 'Staff',
              onTap: () => _openCategory('Staff Vehicles', status: 'Staf')),
          WebNavTab(icon: FontAwesomeIcons.graduationCap, label: 'Student',
              onTap: () => _openCategory('Student Vehicles', status: 'Pelajar')),
          WebNavTab(icon: FontAwesomeIcons.userClock,    label: 'Visitor',
              onTap: () => _openCategory('Visitor Vehicles', status: 'Pelawat')),
          WebNavTab(icon: FontAwesomeIcons.helmetSafety, label: 'Contractor',
              onTap: () => _openCategory('Contractor Vehicles', status: 'Kontraktor')),
          WebNavTab(icon: FontAwesomeIcons.flag,         label: 'Report',
              onTap: () => Navigator.pushNamed(context, '/report_vehicle', arguments: {
                    'reporterId': widget.userId,
                    'reporterName': widget.name,
                    'reporterEmail': widget.email,
                    'reporterRole': widget.role,
                  })),
          WebNavTab(icon: FontAwesomeIcons.circleUser,   label: 'Profile',
              onTap: () => Navigator.pushNamed(context, '/profile', arguments: {
                    'userId': widget.userId, 'name': widget.name, 'email': widget.email,
                  })),
          if (isAdmin)
            WebNavTab(icon: FontAwesomeIcons.users,      label: 'Users',
                onTap: () => Navigator.push(context, MaterialPageRoute(
                    builder: (_) => const AccountManagementScreen(target: 'user')))),
          if (isAdmin)
            WebNavTab(icon: FontAwesomeIcons.userShield, label: 'Admin',
                onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminPanelScreen()))),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _loadStats,
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              _welcomeCard(isAdmin),
              const SizedBox(height: 16),
              _realtimeRow(),
              const SizedBox(height: 22),
              const WebSectionTitle(title: 'Vehicle Statistics', icon: FontAwesomeIcons.chartColumn),
              _statsGrid(),
              const SizedBox(height: 22),
              const WebSectionTitle(title: 'Modules', icon: FontAwesomeIcons.gripVertical),
              _moduleGrid(isAdmin),
              const SizedBox(height: 24),
              _footer(),
            ],
          ),
        ),
      ),
    );
  }

  // ============== welcome card with avatar + role pill =================
  Widget _welcomeCard(bool isAdmin) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          begin: Alignment.centerLeft, end: Alignment.centerRight,
          colors: AppColors.heroGradient,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(color: AppColors.primary.withValues(alpha: 0.25), blurRadius: 14, offset: const Offset(0, 6)),
        ],
      ),
      child: Row(
        children: [
          CircleAvatar(
            radius: 28,
            backgroundColor: Colors.white,
            backgroundImage: profileImage != null ? FileImage(profileImage!) : null,
            child: profileImage == null
                ? const FaIcon(FontAwesomeIcons.user, color: AppColors.primary, size: 22) : null,
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(widget.name.isEmpty ? 'Welcome' : 'Hi, ${widget.name.split(' ').first}',
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 17)),
                const SizedBox(height: 2),
                Text(widget.email,
                    style: TextStyle(color: Colors.white.withValues(alpha: 0.85), fontSize: 12),
                    overflow: TextOverflow.ellipsis),
                const SizedBox(height: 6),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3),
                  decoration: BoxDecoration(color: AppColors.brandYellow, borderRadius: BorderRadius.circular(20)),
                  child: Text(isAdmin ? 'ADMINISTRATOR' : 'USER',
                      style: const TextStyle(color: AppColors.dark, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.6)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // ============== real-time clock + date + total users 3-col row ==================
  Widget _realtimeRow() {
    return IntrinsicHeight(
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Expanded(child: _infoBox(
            icon: FontAwesomeIcons.clock,
            label: 'Time',
            value: currentTime.isEmpty ? '--:--:--' : currentTime,
            gradient: AppColors.heroGradient,
          )),
          const SizedBox(width: 10),
          Expanded(child: _infoBox(
            icon: FontAwesomeIcons.calendar,
            label: 'Date',
            value: currentDate.isEmpty ? '--' : currentDate,
            gradient: AppColors.visitorGradient,
          )),
          const SizedBox(width: 10),
          Expanded(child: _infoBox(
            icon: FontAwesomeIcons.users,
            label: 'Users',
            value: _totalUsers?.toString() ?? '—',
            gradient: AppColors.studentGradient,
          )),
        ],
      ),
    );
  }

  Widget _infoBox({required IconData icon, required String label, required String value, required List<Color> gradient}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(12),
        border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
        boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.05), blurRadius: 8, offset: const Offset(0, 2))],
      ),
      child: Column(
        children: [
          Container(
            width: 36, height: 36,
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: gradient, begin: Alignment.topLeft, end: Alignment.bottomRight),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Center(child: FaIcon(icon, size: 14, color: Colors.white)),
          ),
          const SizedBox(height: 8),
          FittedBox(
            fit: BoxFit.scaleDown,
            child: Text(value, style: AppTypography.statCount(size: 18, color: AppColors.dark)),
          ),
          const SizedBox(height: 2),
          Text(label, style: const TextStyle(color: AppColors.mutedText, fontSize: 11, fontWeight: FontWeight.w600)),
        ],
      ),
    );
  }

  // ============== 2-col stat-card grid with live counts ==============
  Widget _statsGrid() {
    final tiles = [
      _BigStatTile('Staff Vehicles',      FontAwesomeIcons.briefcase,    AppColors.staffGradient,      AppColors.statStaffBorder,      _staffCount,      _pct(_staffCount),      () => _openCategory('Staff Vehicles', status: 'Staf')),
      _BigStatTile('Student Vehicles',    FontAwesomeIcons.graduationCap, AppColors.studentGradient,    AppColors.statStudentBorder,    _studentCount,    _pct(_studentCount),    () => _openCategory('Student Vehicles', status: 'Pelajar')),
      _BigStatTile('Visitor Vehicles',    FontAwesomeIcons.userClock,    AppColors.visitorGradient,    AppColors.statVisitorBorder,    _visitorCount,    _pct(_visitorCount),    () => _openCategory('Visitor Vehicles', status: 'Pelawat')),
      _BigStatTile('Contractor Vehicles', FontAwesomeIcons.helmetSafety, AppColors.contractorGradient, AppColors.statContractorBorder, _contractorCount, _pct(_contractorCount), () => _openCategory('Contractor Vehicles', status: 'Kontraktor')),
    ];
    return Column(
      children: [
        GridView.builder(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
            crossAxisCount: 2, mainAxisSpacing: 12, crossAxisSpacing: 12, childAspectRatio: 0.92,
          ),
          itemCount: tiles.length,
          itemBuilder: (_, i) => tiles[i].build(_statsLoading),
        ),
        const SizedBox(height: 12),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 18),
          decoration: BoxDecoration(
            gradient: const LinearGradient(colors: AppColors.totalGradient, begin: Alignment.topLeft, end: Alignment.bottomRight),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.12), blurRadius: 16, offset: const Offset(0, 6))],
          ),
          child: Row(
            children: [
              Container(
                width: 56, height: 56,
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(14),
                ),
                child: const Center(child: FaIcon(FontAwesomeIcons.car, size: 22, color: Colors.white)),
              ),
              const SizedBox(width: 16),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(_totalVehicles?.toString() ?? '—',
                      style: AppTypography.statCount(size: 36, color: Colors.white)),
                  const SizedBox(height: 2),
                  const Text('Total Vehicles',
                      style: TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.w600)),
                  const SizedBox(height: 2),
                  Text('All categories', style: TextStyle(color: Colors.white.withValues(alpha: 0.8), fontSize: 11)),
                ],
              ),
            ],
          ),
        ),
      ],
    );
  }

  // ============== module grid (3-col compact) ==================
  Widget _moduleGrid(bool isAdmin) {
    final mods = <_ModTile>[
      _ModTile('Search', FontAwesomeIcons.magnifyingGlass, AppColors.primary,
          () => Navigator.pushNamed(context, '/search_car')),
      _ModTile('Report Vehicle', FontAwesomeIcons.flag, AppColors.uitmRed,
          () => Navigator.pushNamed(context, '/report_vehicle', arguments: {
                'reporterId': widget.userId,
                'reporterName': widget.name,
                'reporterEmail': widget.email,
                'reporterRole': widget.role,
              })),
      _ModTile('All Vehicles', FontAwesomeIcons.carRear, AppColors.secondary,
          () => _openCategory('All Vehicles', showAll: true)),
      _ModTile('About', FontAwesomeIcons.circleInfo, AppColors.dark,
          () => Navigator.pushNamed(context, '/about_system')),
      if (isAdmin)
        _ModTile('Admin Panel', FontAwesomeIcons.userTie, AppColors.dark,
            () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminPanelScreen()))),
      if (isAdmin)
        _ModTile('Reports', FontAwesomeIcons.flagCheckered, AppColors.uitmRed,
            () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ReportsListScreen()))),
      if (isAdmin)
        _ModTile('Manage Users', FontAwesomeIcons.users, AppColors.primary,
            () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountManagementScreen(target: 'user')))),
      if (isAdmin)
        _ModTile('Manage Admins', FontAwesomeIcons.userShield, AppColors.primary,
            () => Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountManagementScreen(target: 'admin')))),
      if (isAdmin)
        _ModTile('Bulk Import', FontAwesomeIcons.fileArrowUp, AppColors.success,
            () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BulkImportScreen()))),
    ];
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3, mainAxisSpacing: 10, crossAxisSpacing: 10, childAspectRatio: 0.92,
      ),
      itemCount: mods.length,
      itemBuilder: (_, i) => mods[i].build(),
    );
  }

  Widget _footer() => Container(
        padding: const EdgeInsets.symmetric(vertical: 18),
        alignment: Alignment.center,
        child: Text(
          '© 2026 NEO V-TRACK · UiTM Cawangan Johor',
          style: TextStyle(color: AppColors.mutedText.withValues(alpha: 0.8), fontSize: 11),
        ),
      );
}

/// Beefier stat card matching web `.stat-card` proportions:
/// 16 px radius, 28 px padding, 60×60 gradient icon disc, 44 px number, footer line.
class _BigStatTile {
  final String label;
  final IconData icon;
  final List<Color> gradient;
  final Color border;
  final int? count;
  final String pct;
  final VoidCallback onTap;
  const _BigStatTile(this.label, this.icon, this.gradient, this.border, this.count, this.pct, this.onTap);

  Widget build(bool loading) => Builder(builder: (ctx) {
        return Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(16),
            child: Container(
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border(top: BorderSide(color: border, width: 4),
                    left: const BorderSide(color: AppColors.cardBorder),
                    right: const BorderSide(color: AppColors.cardBorder),
                    bottom: const BorderSide(color: AppColors.cardBorder)),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.10), blurRadius: 16, offset: const Offset(0, 8))],
              ),
              padding: const EdgeInsets.fromLTRB(16, 18, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    width: 54, height: 54,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(begin: Alignment.topLeft, end: Alignment.bottomRight, colors: gradient),
                      borderRadius: BorderRadius.circular(13),
                      boxShadow: [BoxShadow(color: gradient.last.withValues(alpha: 0.35), blurRadius: 10, offset: const Offset(0, 4))],
                    ),
                    child: Center(child: FaIcon(icon, color: Colors.white, size: 22)),
                  ),
                  const SizedBox(height: 14),
                  loading
                      ? const SizedBox(
                          height: 36,
                          child: Center(child: SizedBox(width: 18, height: 18, child: CircularProgressIndicator(strokeWidth: 2))),
                        )
                      : Text(count?.toString() ?? '0', style: AppTypography.statCount(size: 32, color: AppColors.dark)),
                  const SizedBox(height: 2),
                  Text(label,
                      maxLines: 1, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: AppColors.mutedText, fontWeight: FontWeight.w600, fontSize: 13)),
                  const SizedBox(height: 8),
                  Row(children: [
                    const FaIcon(FontAwesomeIcons.chartLine, size: 10, color: AppColors.success),
                    const SizedBox(width: 4),
                    Flexible(
                      child: Text('$pct from total',
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(color: AppColors.success, fontSize: 11, fontWeight: FontWeight.w600)),
                    ),
                  ]),
                ],
              ),
            ),
          ),
        );
      });
}

class _ModTile {
  final String label;
  final IconData icon;
  final Color color;
  final VoidCallback onTap;
  const _ModTile(this.label, this.icon, this.color, this.onTap);

  Widget build() => Builder(builder: (ctx) {
        return Material(
          color: Colors.transparent,
          child: InkWell(
            onTap: onTap,
            borderRadius: BorderRadius.circular(10),
            child: Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(10),
                border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
              ),
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 40, height: 40,
                    decoration: BoxDecoration(
                      color: color.withValues(alpha: 0.1),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Center(child: FaIcon(icon, color: color, size: 18)),
                  ),
                  const SizedBox(height: 8),
                  Text(label,
                      maxLines: 2, textAlign: TextAlign.center, overflow: TextOverflow.ellipsis,
                      style: const TextStyle(color: AppColors.dark, fontWeight: FontWeight.w600, fontSize: 11.5, height: 1.2)),
                ],
              ),
            ),
          ),
        );
      });
}
