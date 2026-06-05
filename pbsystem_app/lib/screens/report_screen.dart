import 'dart:io';
import 'package:flutter/material.dart';
import 'package:geolocator/geolocator.dart';
import 'package:image_picker/image_picker.dart';
import '../models.dart';
import '../theme.dart';
import '../services/api.dart';

class ReportScreen extends StatefulWidget {
  final AppUser reporter;
  final Vehicle? vehicle;
  const ReportScreen({super.key, required this.reporter, this.vehicle});
  @override
  State<ReportScreen> createState() => _ReportScreenState();
}

class _ReportScreenState extends State<ReportScreen> {
  final _plate = TextEditingController();
  final _offense = TextEditingController();
  final _picker = ImagePicker();
  final List<File> _photos = [];
  Position? _pos;
  bool _locating = false;
  bool _submitting = false;
  String? _locError;

  @override
  void initState() {
    super.initState();
    if (widget.vehicle != null) _plate.text = widget.vehicle!.plate;
    _getLocation();
  }

  @override
  void dispose() {
    _plate.dispose();
    _offense.dispose();
    super.dispose();
  }

  Future<void> _getLocation() async {
    setState(() {
      _locating = true;
      _locError = null;
    });
    try {
      if (!await Geolocator.isLocationServiceEnabled()) {
        throw 'Location services are disabled.';
      }
      var perm = await Geolocator.checkPermission();
      if (perm == LocationPermission.denied) perm = await Geolocator.requestPermission();
      if (perm == LocationPermission.denied || perm == LocationPermission.deniedForever) {
        throw 'Location permission denied.';
      }
      final p = await Geolocator.getCurrentPosition();
      if (mounted) setState(() => _pos = p);
    } catch (e) {
      if (mounted) setState(() => _locError = e.toString());
    } finally {
      if (mounted) setState(() => _locating = false);
    }
  }

  Future<void> _addPhoto(ImageSource source) async {
    final x = await _picker.pickImage(source: source, imageQuality: 70, maxWidth: 1600);
    if (x != null && mounted) setState(() => _photos.add(File(x.path)));
  }

  void _pickSource() {
    showModalBottomSheet(
      context: context,
      builder: (_) => SafeArea(
        child: Wrap(children: [
          ListTile(
            leading: const Icon(Icons.camera_alt_outlined),
            title: const Text('Take a photo'),
            onTap: () {
              Navigator.pop(context);
              _addPhoto(ImageSource.camera);
            },
          ),
          ListTile(
            leading: const Icon(Icons.photo_library_outlined),
            title: const Text('Choose from gallery'),
            onTap: () {
              Navigator.pop(context);
              _addPhoto(ImageSource.gallery);
            },
          ),
        ]),
      ),
    );
  }

  void _snack(String msg, {bool error = false}) {
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(content: Text(msg), backgroundColor: error ? NV.danger : NV.ok),
    );
  }

  Future<void> _submit() async {
    if (_plate.text.trim().isEmpty) return _snack('Enter a plate number.', error: true);
    if (_offense.text.trim().isEmpty) return _snack('Describe the offense.', error: true);
    if (_pos == null) return _snack('Location is required. Retry location.', error: true);
    if (_photos.isEmpty) return _snack('Add at least one photo.', error: true);

    setState(() => _submitting = true);
    try {
      final id = await Api.submitReport(
        vehicle: widget.vehicle,
        plate: _plate.text.trim().toUpperCase(),
        offense: _offense.text.trim(),
        latitude: _pos!.latitude,
        longitude: _pos!.longitude,
        photos: _photos,
        reporter: widget.reporter,
      );
      if (!mounted) return;
      _snack('Report #$id submitted.');
      Navigator.pop(context);
    } catch (e) {
      _snack(e.toString(), error: true);
    } finally {
      if (mounted) setState(() => _submitting = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Report a vehicle')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          TextField(
            controller: _plate,
            textCapitalization: TextCapitalization.characters,
            readOnly: widget.vehicle != null,
            decoration: const InputDecoration(labelText: 'Plate number', prefixIcon: Icon(Icons.pin_outlined)),
          ),
          if (widget.vehicle != null)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Text('${widget.vehicle!.name} · ${widget.vehicle!.categoryLabel}',
                  style: const TextStyle(color: NV.muted, fontSize: 13)),
            ),
          const SizedBox(height: 14),
          TextField(
            controller: _offense,
            maxLines: 3,
            decoration: const InputDecoration(
                labelText: 'Offense details', alignLabelWithHint: true, prefixIcon: Icon(Icons.description_outlined)),
          ),
          const SizedBox(height: 16),
          _locationCard(),
          const SizedBox(height: 16),
          _photosCard(),
          const SizedBox(height: 24),
          FilledButton.icon(
            onPressed: _submitting ? null : _submit,
            icon: _submitting
                ? const SizedBox(width: 18, height: 18, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                : const Icon(Icons.send),
            label: Text(_submitting ? 'Submitting…' : 'Submit report'),
          ),
        ],
      ),
    );
  }

  Widget _locationCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Row(children: [
          Icon(Icons.location_on_outlined, color: _pos != null ? NV.ok : NV.warn),
          const SizedBox(width: 12),
          Expanded(
            child: _locating
                ? const Text('Getting location…')
                : _pos != null
                    ? Text('Location captured\n${_pos!.latitude.toStringAsFixed(5)}, ${_pos!.longitude.toStringAsFixed(5)}',
                        style: const TextStyle(fontSize: 13))
                    : Text(_locError ?? 'Location not set', style: const TextStyle(color: NV.warn, fontSize: 13)),
          ),
          TextButton(onPressed: _locating ? null : _getLocation, child: const Text('Retry')),
        ]),
      ),
    );
  }

  Widget _photosCard() {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(14),
        child: Column(crossAxisAlignment: CrossAxisAlignment.start, children: [
          Row(children: [
            const Icon(Icons.photo_camera_outlined, color: NV.muted),
            const SizedBox(width: 8),
            Text('Photos (${_photos.length})', style: const TextStyle(fontWeight: FontWeight.w700)),
            const Spacer(),
            TextButton.icon(onPressed: _pickSource, icon: const Icon(Icons.add_a_photo_outlined, size: 18), label: const Text('Add')),
          ]),
          if (_photos.isNotEmpty)
            SizedBox(
              height: 90,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: _photos.length,
                separatorBuilder: (_, _) => const SizedBox(width: 8),
                itemBuilder: (_, i) => Stack(children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: Image.file(_photos[i], width: 90, height: 90, fit: BoxFit.cover),
                  ),
                  Positioned(
                    right: 0,
                    top: 0,
                    child: GestureDetector(
                      onTap: () => setState(() => _photos.removeAt(i)),
                      child: Container(
                        decoration: const BoxDecoration(color: Colors.black54, shape: BoxShape.circle),
                        child: const Icon(Icons.close, color: Colors.white, size: 18),
                      ),
                    ),
                  ),
                ]),
              ),
            )
          else
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 8),
              child: Text('At least one photo is required.', style: TextStyle(color: NV.muted, fontSize: 12)),
            ),
        ]),
      ),
    );
  }
}
