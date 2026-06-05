import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models.dart';

/// Persists the logged-in user across launches.
class Session {
  static const _key = 'nv_user';
  static AppUser? current;

  static Future<AppUser?> load() async {
    final prefs = await SharedPreferences.getInstance();
    final raw = prefs.getString(_key);
    if (raw == null) return null;
    try {
      current = AppUser.fromStored(json.decode(raw) as Map<String, dynamic>);
      return current;
    } catch (_) {
      return null;
    }
  }

  static Future<void> save(AppUser user) async {
    current = user;
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_key, json.encode(user.toJson()));
  }

  static Future<void> clear() async {
    current = null;
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_key);
  }
}
