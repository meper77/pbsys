import 'package:flutter/material.dart';
import '../services/api_service.dart';

class VehicleDetailScreen extends StatelessWidget {
  const VehicleDetailScreen({super.key, required this.vehicle});

  final Map<String, dynamic> vehicle;

  @override
  Widget build(BuildContext context) {
    final platenum = (vehicle['platenum'] ?? '-').toString().toUpperCase();
    final owner = (vehicle['name'] ?? '-').toString();
    final idnumber = (vehicle['idnumber'] ?? '-').toString();
    final phone = (vehicle['phone'] ?? '-').toString();
    final type = (vehicle['type'] ?? '-').toString();
    final status = (vehicle['status'] ?? '-').toString();
    final sticker = (vehicle['sticker'] ?? '-').toString();

    return Scaffold(
      appBar: AppBar(
        title: Text('Vehicle: $platenum'),
      ),
      body: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text('PLATE: $platenum', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            Text('OWNER: $owner'),
            const SizedBox(height: 6),
            Text('ID NUMBER: $idnumber'),
            const SizedBox(height: 6),
            Text('PHONE: $phone'),
            const SizedBox(height: 6),
            Text('TYPE: $type'),
            const SizedBox(height: 6),
            Text('STATUS: $status'),
            const SizedBox(height: 6),
            Text('STICKER: $sticker'),
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Back'),
            ),
            const SizedBox(height: 10),
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () {
                  Navigator.pushNamed(
                    context,
                    '/report_vehicle',
                    arguments: {
                      'vehicle': vehicle,
                      'reporterId': ApiService.currentUserId,
                      'reporterName': ApiService.currentUserName,
                      'reporterEmail': ApiService.currentUserEmail,
                      'reporterRole': ApiService.currentUserRole,
                    },
                  );
                },
                icon: const Icon(Icons.report_problem),
                label: const Text('Report Offense'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
