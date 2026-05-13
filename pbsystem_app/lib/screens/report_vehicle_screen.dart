import 'dart:io';

import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:geolocator/geolocator.dart';

import '../services/api_service.dart';
import '../services/report_service.dart';

class ReportVehicleScreen extends StatefulWidget {
  const ReportVehicleScreen({
    super.key,
    this.vehicle,
    this.reporterId = 0,
    this.reporterName = '',
    this.reporterEmail = '',
    this.reporterRole = 'user',
  });

  final Map<String, dynamic>? vehicle;
  final int reporterId;
  final String reporterName;
  final String reporterEmail;
  final String reporterRole;

  @override
  State<ReportVehicleScreen> createState() => _ReportVehicleScreenState();
}

class _ReportVehicleScreenState extends State<ReportVehicleScreen> {
  final _plateController = TextEditingController();
  final _ownerController = TextEditingController();
  final _idController = TextEditingController();
  final _phoneController = TextEditingController();
  final _typeController = TextEditingController();
  final _statusController = TextEditingController();
  final _stickerController = TextEditingController();
  final _offenseController = TextEditingController();

  final ImagePicker _picker = ImagePicker();
  final ReportService _reportService = ReportService();
  final List<File> _photos = [];

  bool _submitting = false;
  bool _loadingLocation = true;
  String _message = '';
  Position? _position;

  @override
  void initState() {
    super.initState();
    _prefillVehicle();
    _loadLocation();
  }

  @override
  void dispose() {
    _plateController.dispose();
    _ownerController.dispose();
    _idController.dispose();
    _phoneController.dispose();
    _typeController.dispose();
    _statusController.dispose();
    _stickerController.dispose();
    _offenseController.dispose();
    super.dispose();
  }

  void _prefillVehicle() {
    final vehicle = widget.vehicle;
    if (vehicle == null) {
      return;
    }

    _plateController.text = (vehicle['platenum'] ?? '').toString();
    _ownerController.text = (vehicle['name'] ?? '').toString();
    _idController.text = (vehicle['idnumber'] ?? '').toString();
    _phoneController.text = (vehicle['phone'] ?? '').toString();
    _typeController.text = (vehicle['type'] ?? '').toString();
    _statusController.text = (vehicle['status'] ?? '').toString();
    _stickerController.text = (vehicle['sticker'] ?? '').toString();
  }

  Future<void> _loadLocation() async {
    try {
      final enabled = await Geolocator.isLocationServiceEnabled();
      if (!enabled) {
        throw StateError('Location services are disabled');
      }

      var permission = await Geolocator.checkPermission();
      if (permission == LocationPermission.denied) {
        permission = await Geolocator.requestPermission();
      }

      if (permission == LocationPermission.denied) {
        throw StateError('Location permission denied');
      }

      if (permission == LocationPermission.deniedForever) {
        throw StateError('Location permission permanently denied');
      }

      final position = await Geolocator.getCurrentPosition(
        desiredAccuracy: LocationAccuracy.high,
      );

      if (!mounted) return;
      setState(() {
        _position = position;
        _loadingLocation = false;
      });
    } catch (e) {
      if (!mounted) return;
      setState(() {
        _loadingLocation = false;
        _message = 'Unable to get location: $e';
      });
    }
  }

  Future<void> _addPhoto(ImageSource source) async {
    final picked = await _picker.pickImage(
      source: source,
      imageQuality: 80,
      maxWidth: 1600,
    );

    if (picked == null) return;

    setState(() {
      _photos.add(File(picked.path));
      _message = '';
    });
  }

  void _removePhoto(int index) {
    setState(() {
      _photos.removeAt(index);
    });
  }

  Future<void> _submit() async {
    final plate = _plateController.text.trim();
    final offense = _offenseController.text.trim();

    if (plate.isEmpty || offense.isEmpty) {
      setState(() {
        _message = 'Plate number and offense details are required.';
      });
      return;
    }

    if (_photos.isEmpty) {
      setState(() {
        _message = 'Please add at least one vehicle photo.';
      });
      return;
    }

    if (_position == null) {
      setState(() {
        _message = 'Location is not ready yet.';
      });
      return;
    }

    setState(() {
      _submitting = true;
      _message = '';
    });

    final data = await _reportService.submitReport(
      photos: _photos,
      plateNumber: plate,
      offense: offense,
      latitude: _position!.latitude,
      longitude: _position!.longitude,
      ownerName: _ownerController.text.trim(),
      idNumber: _idController.text.trim(),
      phone: _phoneController.text.trim(),
      vehicleType: _typeController.text.trim(),
      vehicleStatus: _statusController.text.trim(),
      sticker: _stickerController.text.trim(),
      reporterId: widget.reporterId != 0 ? widget.reporterId : ApiService.currentUserId,
      reporterName: widget.reporterName.isNotEmpty ? widget.reporterName : ApiService.currentUserName,
      reporterEmail: widget.reporterEmail.isNotEmpty ? widget.reporterEmail : ApiService.currentUserEmail,
      reporterRole: widget.reporterRole.isNotEmpty ? widget.reporterRole : ApiService.currentUserRole,
    );

    if (!mounted) return;

    setState(() {
      _submitting = false;
      _message = (data['message'] ?? 'Report submitted').toString();
      if (data['success'] == 1) {
        _offenseController.clear();
        _photos.clear();
      }
    });
  }

  Widget _field(String label, TextEditingController controller, {int maxLines = 1, bool readOnly = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        controller: controller,
        maxLines: maxLines,
        readOnly: readOnly,
        decoration: InputDecoration(labelText: label),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final coords = _position == null
        ? 'Fetching coordinates...'
        : '${_position!.latitude.toStringAsFixed(6)}, ${_position!.longitude.toStringAsFixed(6)}';

    return Scaffold(
      appBar: AppBar(
        title: const Text('Report Vehicle'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            if (_message.isNotEmpty) ...[
              Text(
                _message,
                style: TextStyle(
                  color: _message.toLowerCase().contains('success') ? Colors.green : Colors.red,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 12),
            ],
            _field('Plate Number', _plateController),
            _field('Owner Name', _ownerController, readOnly: widget.vehicle != null),
            _field('ID Number', _idController, readOnly: widget.vehicle != null),
            _field('Phone', _phoneController, readOnly: widget.vehicle != null),
            _field('Vehicle Type', _typeController, readOnly: widget.vehicle != null),
            _field('Vehicle Status', _statusController, readOnly: widget.vehicle != null),
            _field('Sticker', _stickerController, readOnly: widget.vehicle != null),
            TextField(
              controller: _offenseController,
              maxLines: 4,
              decoration: const InputDecoration(
                labelText: 'Offense Details',
                hintText: 'Describe the offense here',
              ),
            ),
            const SizedBox(height: 16),
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _submitting ? null : () => _addPhoto(ImageSource.camera),
                    icon: const Icon(Icons.photo_camera),
                    label: const Text('Snap Photo'),
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _submitting ? null : () => _addPhoto(ImageSource.gallery),
                    icon: const Icon(Icons.photo_library),
                    label: const Text('Upload'),
                  ),
                ),
              ],
            ),
            const SizedBox(height: 12),
            if (_photos.isNotEmpty)
              SizedBox(
                height: 100,
                child: ListView.separated(
                  scrollDirection: Axis.horizontal,
                  itemCount: _photos.length,
                  separatorBuilder: (_, __) => const SizedBox(width: 8),
                  itemBuilder: (context, index) {
                    return Stack(
                      children: [
                        ClipRRect(
                          borderRadius: BorderRadius.circular(12),
                          child: Image.file(
                            _photos[index],
                            width: 100,
                            height: 100,
                            fit: BoxFit.cover,
                          ),
                        ),
                        Positioned(
                          top: 2,
                          right: 2,
                          child: CircleAvatar(
                            radius: 12,
                            backgroundColor: Colors.black87,
                            child: IconButton(
                              padding: EdgeInsets.zero,
                              iconSize: 14,
                              color: Colors.white,
                              onPressed: () => _removePhoto(index),
                              icon: const Icon(Icons.close),
                            ),
                          ),
                        ),
                      ],
                    );
                  },
                ),
              ),
            const SizedBox(height: 16),
            Card(
              child: ListTile(
                leading: const Icon(Icons.my_location),
                title: const Text('Coordinates'),
                subtitle: Text(_loadingLocation ? 'Fetching location...' : coords),
                trailing: IconButton(
                  onPressed: _loadingLocation ? null : _loadLocation,
                  icon: const Icon(Icons.refresh),
                ),
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              height: 52,
              child: ElevatedButton(
                onPressed: _submitting ? null : _submit,
                child: _submitting
                    ? const CircularProgressIndicator(color: Colors.white)
                    : const Text('SUBMIT REPORT'),
              ),
            ),
          ],
        ),
      ),
    );
  }
}
