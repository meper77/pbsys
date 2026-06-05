import 'package:flutter/material.dart';
import '../models.dart';
import '../theme.dart';
import '../services/api.dart';
import '../services/session.dart';
import 'login_screen.dart';
import 'search_screen.dart';
import 'vehicle_list_screen.dart';
import 'report_screen.dart';

class HomeScreen extends StatefulWidget {
  final AppUser user;
  const HomeScreen({super.key, required this.user});
  @override
  State<HomeScreen> createState() => _HomeScreenState();
}

class _HomeScreenState extends State<HomeScreen> {
  Future<Stats>? _stats;

  @override
  void initState() {
    super.initState();
    _stats = Api.stats();
  }

  Future<void> _refresh() async {
    setState(() => _stats = Api.stats());
    await _stats;
  }

  Future<void> _logout() async {
    await Session.clear();
    if (!mounted) return;
    Navigator.of(context).pushAndRemoveUntil(
        MaterialPageRoute(builder: (_) => const LoginScreen()), (r) => false);
  }

  void _openCategory(String type, String title) {
    Navigator.push(context, MaterialPageRoute(builder: (_) => VehicleListScreen(type: type, title: title)));
  }

  @override
  Widget build(BuildContext context) {
    final cats = [
      ('staff', 'Staff', Icons.badge_outlined),
      ('student', 'Student', Icons.school_outlined),
      ('visitor', 'Visitor', Icons.person_pin_circle_outlined),
      ('contractor', 'Contractor', Icons.engineering_outlined),
    ];
    return Scaffold(
      appBar: AppBar(
        title: const Text('NEO V-TRACK', style: TextStyle(fontWeight: FontWeight.w800)),
        actions: [
          IconButton(onPressed: _logout, icon: const Icon(Icons.logout), tooltip: 'Sign out'),
        ],
      ),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            Text('Hi, ${widget.user.name.isEmpty ? widget.user.email : widget.user.name}',
                style: const TextStyle(fontSize: 20, fontWeight: FontWeight.w800, color: NV.ink)),
            Text(widget.user.isAdmin ? 'Administrator' : 'Officer',
                style: const TextStyle(color: NV.muted, fontSize: 13)),
            const SizedBox(height: 16),
            FutureBuilder<Stats>(
              future: _stats,
              builder: (context, snap) {
                final s = snap.data ?? Stats();
                final loading = snap.connectionState == ConnectionState.waiting;
                return Column(
                  children: [
                    _TotalCard(total: s.total, users: s.totalUsers, loading: loading),
                    const SizedBox(height: 12),
                    GridView.count(
                      crossAxisCount: 2,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 12,
                      crossAxisSpacing: 12,
                      childAspectRatio: 1.5,
                      children: [
                        for (final c in cats)
                          _CatCard(
                            label: c.$2,
                            icon: c.$3,
                            count: {'staff': s.staff, 'student': s.student, 'visitor': s.visitor, 'contractor': s.contractor}[c.$1] ?? 0,
                            color: NV.categoryColor(c.$1),
                            onTap: () => _openCategory(c.$1, c.$2),
                          ),
                      ],
                    ),
                  ],
                );
              },
            ),
            const SizedBox(height: 20),
            Row(children: [
              Expanded(
                child: _ActionButton(
                  icon: Icons.search,
                  label: 'Search',
                  onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SearchScreen())),
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _ActionButton(
                  icon: Icons.report_gmailerrorred_outlined,
                  label: 'Report',
                  filled: true,
                  onTap: () => Navigator.push(context,
                      MaterialPageRoute(builder: (_) => ReportScreen(reporter: widget.user))),
                ),
              ),
            ]),
          ],
        ),
      ),
    );
  }
}

class _TotalCard extends StatelessWidget {
  final int total, users;
  final bool loading;
  const _TotalCard({required this.total, required this.users, required this.loading});
  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        gradient: const LinearGradient(colors: [NV.navy, NV.purple]),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Row(
        children: [
          Expanded(
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              const Text('Total vehicles', style: TextStyle(color: Colors.white70, fontSize: 13)),
              const SizedBox(height: 4),
              Text(loading ? '—' : '$total',
                  style: const TextStyle(color: Colors.white, fontSize: 34, fontWeight: FontWeight.w800)),
            ]),
          ),
          Column(crossAxisAlignment: CrossAxisAlignment.end, children: [
            const Icon(Icons.people_outline, color: NV.yellow),
            const SizedBox(height: 4),
            Text(loading ? '—' : '$users users', style: const TextStyle(color: Colors.white70, fontSize: 12)),
          ]),
        ],
      ),
    );
  }
}

class _CatCard extends StatelessWidget {
  final String label;
  final IconData icon;
  final int count;
  final Color color;
  final VoidCallback onTap;
  const _CatCard({required this.label, required this.icon, required this.count, required this.color, required this.onTap});
  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: const Color(0x14000000)),
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Icon(icon, color: color),
            Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text('$count', style: const TextStyle(fontSize: 24, fontWeight: FontWeight.w800, color: NV.ink)),
              Text(label, style: const TextStyle(color: NV.muted, fontSize: 13)),
            ]),
          ],
        ),
      ),
    );
  }
}

class _ActionButton extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool filled;
  final VoidCallback onTap;
  const _ActionButton({required this.icon, required this.label, required this.onTap, this.filled = false});
  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 18),
        decoration: BoxDecoration(
          color: filled ? NV.yellow : Colors.white,
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: filled ? NV.yellow : const Color(0x22000000)),
        ),
        child: Column(children: [
          Icon(icon, color: NV.navy),
          const SizedBox(height: 6),
          Text(label, style: const TextStyle(fontWeight: FontWeight.w700, color: NV.navy)),
        ]),
      ),
    );
  }
}
