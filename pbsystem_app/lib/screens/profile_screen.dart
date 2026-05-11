import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/painting.dart';
import 'package:image_picker/image_picker.dart';
import '../services/profile_image_service.dart';

class ProfileScreen extends StatefulWidget {
  final int userId;
  final String name;
  final String email;

  const ProfileScreen({
    super.key,
    required this.userId,
    required this.name,
    required this.email,
  });

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  File? profileImage;
  final ImagePicker picker = ImagePicker();

  @override
  void initState() {
    super.initState();
    _loadImage();
  }

  // ================= LOAD SAVED IMAGE =================
  Future<void> _loadImage() async {
    final img =
        await ProfileImageService.loadProfileImage(widget.userId);

    if (!mounted) return;

    setState(() {
      profileImage = img;
    });
  }

  // ================= PICK IMAGE =================
  Future<void> _pickImage() async {
    final picked = await picker.pickImage(
      source: ImageSource.gallery,
      imageQuality: 80,
    );

    if (picked == null) return;

    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text("Confirm Photo"),
        content: const Text(
            "Do you want to use this photo as your profile picture?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.deepPurple,
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              "Confirm",
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    final newFile = File(picked.path);

    // Save image per user
    await ProfileImageService.saveProfileImage(
        newFile, widget.userId);

    // Clear Flutter image cache
    PaintingBinding.instance.imageCache.clear();
    PaintingBinding.instance.imageCache.clearLiveImages();

    if (!mounted) return;

    setState(() {
      profileImage = newFile;
    });
  }

  // ================= DELETE IMAGE =================
  Future<void> _deleteImage() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text("Delete Photo"),
        content: const Text(
            "Are you sure you want to delete your profile photo?"),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text("Cancel"),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: Colors.red,
              foregroundColor: Colors.white,
            ),
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              "Delete",
              style: TextStyle(fontWeight: FontWeight.bold),
            ),
          ),
        ],
      ),
    );

    if (confirm != true) return;

    await ProfileImageService.deleteProfileImage(
        widget.userId);

    // Clear image cache
    PaintingBinding.instance.imageCache.clear();
    PaintingBinding.instance.imageCache.clearLiveImages();

    if (!mounted) return;

    setState(() {
      profileImage = null;
    });

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(content: Text("Profile photo deleted")),
    );
  }

  // ================= UI =================
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Container(
        width: double.infinity,
        height: double.infinity,
        color: Colors.deepPurple.shade100,
        child: SafeArea(
          child: SingleChildScrollView(
            padding: const EdgeInsets.all(20),
            child: Column(
              children: [
                const SizedBox(height: 20),

                // ===== PROFILE IMAGE =====
                CircleAvatar(
                  radius: 70,
                  backgroundColor: Colors.deepPurple.shade200,
                  backgroundImage:
                      profileImage != null
                          ? FileImage(profileImage!)
                          : null,
                  child: profileImage == null
                      ? const Icon(
                          Icons.person,
                          size: 60,
                          color: Colors.deepPurple,
                        )
                      : null,
                ),

                const SizedBox(height: 20),

                // ===== CHANGE PHOTO BUTTON =====
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _pickImage,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.deepPurple,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text(
                      "Change Photo",
                      style:
                          TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                // ===== DELETE PHOTO BUTTON =====
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _deleteImage,
                    style: ElevatedButton.styleFrom(
                      backgroundColor: Colors.red,
                      foregroundColor: Colors.white,
                    ),
                    child: const Text(
                      "Delete Photo",
                      style:
                          TextStyle(fontWeight: FontWeight.w600),
                    ),
                  ),
                ),

                const SizedBox(height: 30),

                // ===== USER INFO =====
                _infoTile("User ID", widget.userId.toString()),
                _infoTile("Name", widget.name),
                _infoTile("Email", widget.email),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ================= INFO TILE =================
  Widget _infoTile(String label, String value) {
    return Container(
      width: double.infinity,
      margin:
          const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius:
            BorderRadius.circular(12),
      ),
      child: Text(
        "$label: $value",
        softWrap: true,
        overflow: TextOverflow.visible,
      ),
    );
  }
}