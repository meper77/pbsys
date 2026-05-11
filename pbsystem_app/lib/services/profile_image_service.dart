import 'dart:io';
import 'package:path_provider/path_provider.dart';

class ProfileImageService {

  // ================= SAVE IMAGE (PER USER) =================
  static Future<void> saveProfileImage(File image, int userId) async {
    final dir = await getApplicationDocumentsDirectory();
    final path = '${dir.path}/profile_image_$userId.png';
    final savedFile = File(path);

    // Delete old image if exists
    if (await savedFile.exists()) {
      await savedFile.delete();
    }

    final bytes = await image.readAsBytes();
    await savedFile.writeAsBytes(bytes);
  }

  // ================= LOAD IMAGE (PER USER) =================
  static Future<File?> loadProfileImage(int userId) async {
    final dir = await getApplicationDocumentsDirectory();
    final path = '${dir.path}/profile_image_$userId.png';
    final file = File(path);

    if (await file.exists()) {
      return file;
    }
    return null;
  }

  // ================= DELETE IMAGE (PER USER) =================
  static Future<void> deleteProfileImage(int userId) async {
    final dir = await getApplicationDocumentsDirectory();
    final path = '${dir.path}/profile_image_$userId.png';
    final file = File(path);

    if (await file.exists()) {
      await file.delete();
    }
  }
}