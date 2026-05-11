import 'dart:io';
import 'package:flutter/material.dart';
import '../services/profile_image_service.dart';
import 'search_car_screen.dart';

class DashboardScreen extends StatefulWidget {
  final int userId;
  final String name;
  final String email;

  const DashboardScreen({
    super.key,
    required this.userId,
    required this.name,
    required this.email,
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

  @override
  Widget build(BuildContext context) {
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
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [

                // ===== LOGO =====
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

                // ===== TIME =====
                Column(
                  children: [
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
                  ],
                ),

                const SizedBox(height: 12),

                // ===== WELCOME =====
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.symmetric(vertical: 8),
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.6),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Center(
                    child: Text(
                      'Welcome to Neo.V-Track App!',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF4B2E83),
                      ),
                    ),
                  ),
                ),

                const SizedBox(height: 30),

                // ===== PROFILE AVATAR =====
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

                const SizedBox(height: 40),

                // ===== BUTTONS =====
                Row(
                  children: [
                    Expanded(
                      child: Padding(
                        padding: const EdgeInsets.only(right: 12),
                        child: ElevatedButton(
                          onPressed: () {
                            Navigator.push(
                              context,
                              MaterialPageRoute(
                                builder: (_) => const SearchCarScreen(),
                              ),
                            );
                          },
                          style: ElevatedButton.styleFrom(
                            backgroundColor: const Color(0xFFF3C143),
                            padding:
                                const EdgeInsets.symmetric(vertical: 20),
                          ),
                          child: const Text(
                            'SEARCH\nVEHICLES',
                            textAlign: TextAlign.center,
                            style: TextStyle(
                              fontSize: 16,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF4B2E83),
                            ),
                          ),
                        ),
                      ),
                    ),
                    Expanded(
                      child: ElevatedButton(
                        onPressed: _confirmLogout,
                        style: ElevatedButton.styleFrom(
                          backgroundColor: Colors.red,
                          padding:
                              const EdgeInsets.symmetric(vertical: 20),
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

                const SizedBox(height: 12),

                // ===== ABOUT SYSTEM =====
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () {
                      Navigator.pushNamed(context, '/about_system');
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFFF3C143),
                      padding:
                          const EdgeInsets.symmetric(vertical: 16),
                    ),
                    child: const Text(
                      'ABOUT SYSTEM',
                      style: TextStyle(
                        color: Color(0xFF4B2E83),
                        fontWeight: FontWeight.bold,
                        fontSize: 16,
                      ),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}