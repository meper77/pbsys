import 'dart:io';
import 'package:flutter/material.dart';
import '../services/profile_image_service.dart';
import 'admin_panel_screen.dart';

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
  String currentTime = '';
  String currentDate = '';
  File? profileImage;

  @override
  void initState() {
    super.initState();
    _updateDateTime();
    _loadProfileImage();
  }

  void _updateDateTime() {
    final now = DateTime.now();
    currentTime =
        "${now.hour.toString().padLeft(2, '0')}:${now.minute.toString().padLeft(2, '0')}:${now.second.toString().padLeft(2, '0')}";
    currentDate =
        "${now.day.toString().padLeft(2, '0')}-${now.month.toString().padLeft(2, '0')}-${now.year}";
    setState(() {});
    Future.delayed(const Duration(seconds: 1), _updateDateTime);
  }

  Future<void> _loadProfileImage() async {
    final image =
        await ProfileImageService.loadProfileImage(widget.userId);
    setState(() {
      profileImage = image;
    });
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Logout'),
        content: const Text('Are you sure you want to logout?'),
        actions: [
          TextButton(
            child: const Text('Cancel'),
            onPressed: () => Navigator.pop(context),
          ),
          ElevatedButton(
            child: const Text('Logout'),
            onPressed: () {
              Navigator.pop(context);
              Navigator.pop(context);
            },
          ),
        ],
      ),
    );
  }

  Widget _moduleCard({
    required IconData icon,
    required String title,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        width: 160,
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          boxShadow: const [
            BoxShadow(
              color: Colors.black12,
              blurRadius: 10,
              offset: Offset(0, 4),
            ),
          ],
        ),
        child: Column(
          children: [
            Icon(icon, size: 30, color: const Color(0xFF4B2E83)),
            const SizedBox(height: 8),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(
                color: Color(0xFF4B2E83),
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      ),
    );
  }

  void _openCategory(String title, {String status = '', bool showAll = false}) {
    Navigator.pushNamed(
      context,
      '/vehicle_category',
      arguments: {
        'title': title,
        'status': status,
        'showAll': showAll,
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final isAdmin = widget.role == 'admin';

    return Scaffold(
      body: Container(
        decoration: const BoxDecoration(
          gradient: LinearGradient(
            colors: [Color(0xFF4B2E83), Color(0xFFF5F5F5)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Image.asset('assets/images/kik2.png', width: 40, height: 40),
                    const SizedBox(width: 10),
                    const Text(
                      'NEO.V-TRACK',
                      style: TextStyle(
                        fontSize: 24,
                        fontWeight: FontWeight.bold,
                        color: Colors.white,
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 10),
                Text(
                  currentTime,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: Colors.white,
                  ),
                ),
                Text(
                  currentDate,
                  style: const TextStyle(color: Colors.white70),
                ),
                const SizedBox(height: 12),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.6),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Center(
                    child: Text(
                      isAdmin
                          ? 'Welcome Administrator'
                          : 'Welcome to Neo.V-Track App!',
                      style: const TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF4B2E83),
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),
                GestureDetector(
                  onTap: () {
                    Navigator.pushNamed(
                      context,
                      '/profile',
                      arguments: {
                        'userId': widget.userId,
                        'name': widget.name,
                        'email': widget.email,
                      },
                    ).then((_) => _loadProfileImage());
                  },
                  child: Column(
                    children: [
                      CircleAvatar(
                        radius: 38,
                        backgroundColor: Colors.white,
                        backgroundImage: profileImage != null
                            ? FileImage(profileImage!)
                            : null,
                        child: profileImage == null
                            ? const Icon(Icons.person,
                                size: 36, color: Color(0xFF4B2E83))
                            : null,
                      ),
                      const SizedBox(height: 6),
                      Text(
                        widget.name,
                        style: const TextStyle(
                          fontWeight: FontWeight.w600,
                          color: Colors.white,
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                const Align(
                  alignment: Alignment.centerLeft,
                  child: Text(
                    'MODULES',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 10),
                Wrap(
                  spacing: 12,
                  runSpacing: 12,
                  children: [
                    _moduleCard(
                      icon: Icons.search,
                      title: 'Search Vehicles',
                      onTap: () => Navigator.pushNamed(context, '/search_car'),
                    ),
                    _moduleCard(
                      icon: Icons.report_problem,
                      title: 'Report Vehicle',
                      onTap: () => Navigator.pushNamed(
                        context,
                        '/report_vehicle',
                        arguments: {
                          'reporterId': widget.userId,
                          'reporterName': widget.name,
                          'reporterEmail': widget.email,
                          'reporterRole': widget.role,
                        },
                      ),
                    ),
                    _moduleCard(
                      icon: Icons.badge,
                      title: 'Staff Vehicles',
                      onTap: () => _openCategory('Staff Vehicles', status: 'Staf'),
                    ),
                    _moduleCard(
                      icon: Icons.school,
                      title: 'Student Vehicles',
                      onTap: () =>
                          _openCategory('Student Vehicles', status: 'Pelajar'),
                    ),
                    _moduleCard(
                      icon: Icons.person_pin_circle,
                      title: 'Visitor Vehicles',
                      onTap: () =>
                          _openCategory('Visitor Vehicles', status: 'Pelawat'),
                    ),
                    _moduleCard(
                      icon: Icons.build,
                      title: 'Contractor Vehicles',
                      onTap: () => _openCategory(
                        'Contractor Vehicles',
                        status: 'Kontraktor',
                      ),
                    ),
                    _moduleCard(
                      icon: Icons.directions_car_filled,
                      title: 'All Vehicles',
                      onTap: () => _openCategory('All Vehicles', showAll: true),
                    ),
                    if (isAdmin)
                      _moduleCard(
                        icon: Icons.admin_panel_settings,
                        title: 'Admin Panel',
                        onTap: () => Navigator.push(
                          context,
                          MaterialPageRoute(builder: (_) => const AdminPanelScreen()),
                        ),
                      ),
                    _moduleCard(
                      icon: Icons.info_outline,
                      title: 'About System',
                      onTap: () => Navigator.pushNamed(context, '/about_system'),
                    ),
                  ],
                ),
                const SizedBox(height: 18),
                Row(
                  children: [
                    Expanded(
                      child: OutlinedButton(
                        onPressed: () {
                          Navigator.pushNamed(
                            context,
                            '/profile',
                            arguments: {
                              'userId': widget.userId,
                              'name': widget.name,
                              'email': widget.email,
                            },
                          ).then((_) => _loadProfileImage());
                        },
                        style: OutlinedButton.styleFrom(
                          foregroundColor: Colors.white,
                          side: const BorderSide(color: Colors.white),
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: const Text('PROFILE'),
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _confirmLogout,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          padding: const EdgeInsets.symmetric(vertical: 16),
                        ),
                        child: const Text(
                          'LOGOUT',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.bold,
                            color: Colors.white,
                          ),
                        ),
                      ),
                    ),
                    ],
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
