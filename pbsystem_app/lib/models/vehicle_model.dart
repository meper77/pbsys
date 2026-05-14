class Vehicle {
  final String platenum;
  final String name;
  final String idnumber;
  final String phone;
  final String status;
  final String type;
  final String brand;

  Vehicle({
    required this.platenum,
    required this.name,
    required this.idnumber,
    required this.phone,
    required this.status,
    required this.type,
    required this.brand,
  });

  /// Convert JSON map from API to Vehicle object
  factory Vehicle.fromJson(Map<String, dynamic> json) {
    // Capitalize first letter of platenum only
    String formattedPlate = '';
    if (json['platenum'] != null && json['platenum'].toString().isNotEmpty) {
      final str = json['platenum'].toString().toLowerCase();
      formattedPlate = str[0].toUpperCase() + str.substring(1);
    }

    // Brand from API, keep as uppercase
    String brand = json['brand'] != null ? json['brand'].toString().toUpperCase() : '-';

    return Vehicle(
      platenum: formattedPlate.isNotEmpty ? formattedPlate : '-',
      name: json['name'] ?? '-',
      idnumber: json['idnumber'] ?? '-',
      phone: json['phone'] ?? '-',
      status: json['status'] ?? '-',
      type: json['type'] ?? '-',
      brand: brand,
    );
  }

  /// Convert Vehicle object to JSON map
  Map<String, dynamic> toJson() => {
        'platenum': platenum,
        'name': name,
        'idnumber': idnumber,
        'phone': phone,
        'status': status,
        'type': type,
        'brand': brand,
      };
}
