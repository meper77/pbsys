import 'dart:io';
import 'package:flutter/material.dart';
import 'package:flutter/painting.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:image_picker/image_picker.dart';

import '../services/profile_image_service.dart';
import '../theme/app_colors.dart';
import '../widgets/web_app_bar.dart';
import '../widgets/web_gradient_button.dart';

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

  Future<void> _loadImage() async {
    final img = await ProfileImageService.loadProfileImage(widget.userId);
    if (!mounted) return;
    setState(() => profileImage = img);
  }

  Future<void> _pickImage() async {
    final picked = await picker.pickImage(source: ImageSource.gallery, imageQuality: 80);
    if (picked == null) return;
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Confirm Photo'),
        content: const Text('Do you want to use this photo as your profile picture?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(onPressed: () => Navigator.pop(context, true), child: const Text('Confirm')),
        ],
      ),
    );
    if (confirm != true) return;
    final newFile = File(picked.path);
    await ProfileImageService.saveProfileImage(newFile, widget.userId);
    PaintingBinding.instance.imageCache.clear();
    PaintingBinding.instance.imageCache.clearLiveImages();
    if (!mounted) return;
    setState(() => profileImage = newFile);
  }

  Future<void> _deleteImage() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Delete Photo'),
        content: const Text('Are you sure you want to delete your profile photo?'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('Cancel')),
          ElevatedButton(
            style: ElevatedButton.styleFrom(backgroundColor: AppColors.danger),
            onPressed: () => Navigator.pop(context, true),
            child: const Text('Delete'),
          ),
        ],
      ),
    );
    if (confirm != true) return;
    await ProfileImageService.deleteProfileImage(widget.userId);
    PaintingBinding.instance.imageCache.clear();
    PaintingBinding.instance.imageCache.clearLiveImages();
    if (!mounted) return;
    setState(() => profileImage = null);
    ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('Profile photo deleted')));
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.lightBg,
      appBar: const WebAppBar(title: 'My Profile', subtitle: 'Account & photo'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
                border: const Border(top: BorderSide(color: AppColors.uitmRed, width: 3)),
                boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.06), blurRadius: 12, offset: const Offset(0, 4))],
              ),
              child: Column(
                children: [
                  Stack(
                    children: [
                      Container(
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          border: Border.all(color: Colors.white, width: 4),
                          boxShadow: [BoxShadow(color: AppColors.primary.withValues(alpha: 0.18), blurRadius: 14, offset: const Offset(0, 6))],
                        ),
                        child: CircleAvatar(
                          radius: 64,
                          backgroundColor: AppColors.primary.withValues(alpha: 0.1),
                          backgroundImage: profileImage != null ? FileImage(profileImage!) : null,
                          child: profileImage == null
                              ? const FaIcon(FontAwesomeIcons.user, size: 44, color: AppColors.primary)
                              : null,
                        ),
                      ),
                      Positioned(
                        right: 0, bottom: 0,
                        child: Material(
                          color: AppColors.primary,
                          shape: const CircleBorder(),
                          child: InkWell(
                            customBorder: const CircleBorder(),
                            onTap: _pickImage,
                            child: const Padding(
                              padding: EdgeInsets.all(8),
                              child: FaIcon(FontAwesomeIcons.camera, color: Colors.white, size: 12),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Text(widget.name,
                      style: const TextStyle(fontSize: 18, fontWeight: FontWeight.w700, color: AppColors.dark)),
                  const SizedBox(height: 4),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 3),
                    decoration: BoxDecoration(
                      color: AppColors.dark,
                      borderRadius: BorderRadius.circular(20),
                    ),
                    child: const Text('USER',
                        style: TextStyle(color: Colors.white, fontSize: 10, fontWeight: FontWeight.w700, letterSpacing: 0.6)),
                  ),
                  const SizedBox(height: 6),
                  Text(widget.email,
                      style: const TextStyle(color: AppColors.mutedText, fontSize: 13)),
                ],
              ),
            ),
            const SizedBox(height: 18),
            WebGradientButton(
              label: 'Change Photo',
              icon: FontAwesomeIcons.image,
              onPressed: _pickImage,
            ),
            if (profileImage != null) ...[
              const SizedBox(height: 10),
              WebGradientButton(
                label: 'Delete Photo',
                icon: FontAwesomeIcons.trashCan,
                gradient: const [AppColors.uitmRed, AppColors.danger],
                onPressed: _deleteImage,
              ),
            ],
            const SizedBox(height: 18),
            _infoTile(FontAwesomeIcons.hashtag,  'User ID', widget.userId.toString()),
            _infoTile(FontAwesomeIcons.user,     'Name',    widget.name),
            _infoTile(FontAwesomeIcons.envelope, 'Email',   widget.email),
          ],
        ),
      ),
    );
  }

  Widget _infoTile(IconData icon, String label, String value) => Container(
        width: double.infinity,
        margin: const EdgeInsets.only(bottom: 10),
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: const Border.fromBorderSide(BorderSide(color: AppColors.cardBorder)),
        ),
        child: Row(
          children: [
            FaIcon(icon, size: 14, color: AppColors.primary),
            const SizedBox(width: 12),
            SizedBox(width: 80,
              child: Text(label, style: const TextStyle(color: AppColors.mutedText, fontSize: 13)),
            ),
            Expanded(child: Text(value,
                style: const TextStyle(color: AppColors.bodyText, fontWeight: FontWeight.w600))),
          ],
        ),
      );
}
