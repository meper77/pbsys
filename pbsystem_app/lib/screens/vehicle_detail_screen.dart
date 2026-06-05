import 'package:flutter/material.dart';
import '../models.dart';
import '../theme.dart';
import 'report_screen.dart';

/// Reusable list tile for a vehicle.
class VehicleTile extends StatelessWidget {
  final Vehicle vehicle;
  final VoidCallback onTap;
  const VehicleTile({super.key, required this.vehicle, required this.onTap});

  @override
  Widget build(BuildContext context) {
    final color = NV.categoryColor(vehicle.status);
    return Card(
      child: ListTile(
        onTap: onTap,
        leading: Container(
          width: 44,
          height: 44,
          alignment: Alignment.center,
          decoration: BoxDecoration(color: color.withValues(alpha: 0.12), borderRadius: BorderRadius.circular(10)),
          child: Icon(Icons.directions_car, color: color),
        ),
        title: Text(vehicle.plate, style: const TextStyle(fontWeight: FontWeight.w800, letterSpacing: 0.5)),
        subtitle: Text('${vehicle.name} · ${vehicle.categoryLabel}', maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: const Icon(Icons.chevron_right, color: NV.muted),
      ),
    );
  }
}

class VehicleDetailScreen extends StatelessWidget {
  final Vehicle vehicle;
  final AppUser reporter;
  const VehicleDetailScreen({super.key, required this.vehicle, required this.reporter});

  @override
  Widget build(BuildContext context) {
    final color = NV.categoryColor(vehicle.status);
    return Scaffold(
      appBar: AppBar(title: const Text('Vehicle details')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              gradient: LinearGradient(colors: [color, color.withValues(alpha: 0.7)]),
              borderRadius: BorderRadius.circular(16),
            ),
            child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
              Text(vehicle.categoryLabel.toUpperCase(),
                  style: const TextStyle(color: Colors.white70, fontWeight: FontWeight.w700, fontSize: 12, letterSpacing: 1)),
              const SizedBox(height: 6),
              Text(vehicle.plate,
                  style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.w800, letterSpacing: 1)),
            ]),
          ),
          const SizedBox(height: 16),
          _row(Icons.person_outline, 'Owner', vehicle.name),
          _row(Icons.badge_outlined, 'ID number', vehicle.idnumber.isEmpty ? '—' : vehicle.idnumber),
          _row(Icons.phone_outlined, 'Phone', vehicle.phone.isEmpty ? '—' : vehicle.phone),
          _row(Icons.directions_car_outlined, 'Vehicle type', vehicle.type.isEmpty ? '—' : vehicle.type),
          _row(Icons.local_offer_outlined, 'Brand', vehicle.brand.isEmpty ? '—' : vehicle.brand),
          const SizedBox(height: 20),
          FilledButton.icon(
            style: FilledButton.styleFrom(backgroundColor: NV.danger),
            onPressed: () => Navigator.push(context,
                MaterialPageRoute(builder: (_) => ReportScreen(reporter: reporter, vehicle: vehicle))),
            icon: const Icon(Icons.report_gmailerrorred_outlined),
            label: const Text('Report this vehicle'),
          ),
        ],
      ),
    );
  }

  Widget _row(IconData icon, String label, String value) => Card(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          child: Row(children: [
            Icon(icon, color: NV.muted, size: 20),
            const SizedBox(width: 14),
            Text(label, style: const TextStyle(color: NV.muted, fontSize: 13)),
            const Spacer(),
            Flexible(child: Text(value, textAlign: TextAlign.right, style: const TextStyle(fontWeight: FontWeight.w700, color: NV.ink))),
          ]),
        ),
      );
}
