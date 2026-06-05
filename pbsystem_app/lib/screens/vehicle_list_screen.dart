import 'package:flutter/material.dart';
import '../models.dart';
import '../theme.dart';
import '../services/api.dart';
import '../services/session.dart';
import 'vehicle_detail_screen.dart';

class VehicleListScreen extends StatefulWidget {
  final String type; // staff|student|visitor|contractor
  final String title;
  const VehicleListScreen({super.key, required this.type, required this.title});
  @override
  State<VehicleListScreen> createState() => _VehicleListScreenState();
}

class _VehicleListScreenState extends State<VehicleListScreen> {
  Future<List<Vehicle>>? _future;

  @override
  void initState() {
    super.initState();
    _future = Api.vehiclesByType(widget.type);
  }

  Future<void> _refresh() async {
    setState(() => _future = Api.vehiclesByType(widget.type));
    await _future;
  }

  @override
  Widget build(BuildContext context) {
    final reporter = Session.current ?? AppUser(id: 0, name: '', email: '', role: 'user');
    return Scaffold(
      appBar: AppBar(title: Text('${widget.title} vehicles')),
      body: RefreshIndicator(
        onRefresh: _refresh,
        child: FutureBuilder<List<Vehicle>>(
          future: _future,
          builder: (context, snap) {
            if (snap.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }
            if (snap.hasError) {
              return _center(Icons.error_outline, '${snap.error}', NV.danger);
            }
            final list = snap.data ?? [];
            if (list.isEmpty) {
              return _center(Icons.inbox_outlined, 'No ${widget.title.toLowerCase()} vehicles.', NV.muted);
            }
            return ListView.separated(
              padding: const EdgeInsets.all(16),
              itemCount: list.length,
              separatorBuilder: (_, _) => const SizedBox(height: 8),
              itemBuilder: (_, i) => VehicleTile(
                vehicle: list[i],
                onTap: () => Navigator.push(
                  context,
                  MaterialPageRoute(builder: (_) => VehicleDetailScreen(vehicle: list[i], reporter: reporter)),
                ),
              ),
            );
          },
        ),
      ),
    );
  }

  Widget _center(IconData icon, String text, Color color) => ListView(
        children: [
          const SizedBox(height: 120),
          Icon(icon, size: 44, color: color.withValues(alpha: 0.6)),
          const SizedBox(height: 12),
          Text(text, textAlign: TextAlign.center, style: TextStyle(color: color)),
        ],
      );
}
