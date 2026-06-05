// Data models matching the `/api/*` JSON contracts.

class AppUser {
  final int id;
  final String name;
  final String email;
  final String role; // 'admin' | 'user'

  AppUser({required this.id, required this.name, required this.email, required this.role});

  factory AppUser.fromJson(Map<String, dynamic> j, String role) => AppUser(
        id: (j['id'] ?? 0) is int ? (j['id'] ?? 0) : int.tryParse('${j['id']}') ?? 0,
        name: (j['name'] ?? '').toString(),
        email: (j['email'] ?? '').toString(),
        role: role,
      );

  Map<String, dynamic> toJson() => {'id': id, 'name': name, 'email': email, 'role': role};

  factory AppUser.fromStored(Map<String, dynamic> j) =>
      AppUser(id: j['id'] ?? 0, name: j['name'] ?? '', email: j['email'] ?? '', role: j['role'] ?? 'user');

  bool get isAdmin => role == 'admin';
}

/// A vehicle record from the unified `owner` table.
class Vehicle {
  final int id;
  final String plate;
  final String name;
  final String phone;
  final String idnumber;
  final String type;
  final String status; // category: Staf/Pelajar/Pelawat/Kontraktor
  final String brand;

  Vehicle({
    required this.id,
    required this.plate,
    required this.name,
    required this.phone,
    required this.idnumber,
    required this.type,
    required this.status,
    required this.brand,
  });

  factory Vehicle.fromJson(Map<String, dynamic> j) => Vehicle(
        id: (j['id'] is int) ? j['id'] : int.tryParse('${j['id']}') ?? 0,
        plate: (j['plate'] ?? j['platenum'] ?? '').toString(),
        name: (j['name'] ?? '').toString(),
        phone: (j['phone'] ?? '').toString(),
        idnumber: (j['idnumber'] ?? '').toString(),
        type: (j['type'] ?? '').toString(),
        status: (j['status'] ?? '').toString(),
        brand: (j['brand'] ?? '').toString(),
      );

  String get categoryLabel {
    switch (status.toLowerCase()) {
      case 'staf':
        return 'Staff';
      case 'pelajar':
        return 'Student';
      case 'pelawat':
        return 'Visitor';
      case 'kontraktor':
        return 'Contractor';
      default:
        return status;
    }
  }
}

class Stats {
  final int staff, student, visitor, contractor, total, totalUsers;
  Stats({
    this.staff = 0,
    this.student = 0,
    this.visitor = 0,
    this.contractor = 0,
    this.total = 0,
    this.totalUsers = 0,
  });

  factory Stats.fromJson(Map<String, dynamic> s) => Stats(
        staff: s['staff'] ?? 0,
        student: s['student'] ?? 0,
        visitor: s['visitor'] ?? 0,
        contractor: s['contractor'] ?? 0,
        total: s['total'] ?? 0,
        totalUsers: s['total_users'] ?? 0,
      );
}
