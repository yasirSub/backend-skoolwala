<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$CI =& get_instance();
$branch_id = $CI->session->userdata('branch_id');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location-Based Attendance Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .location-card {
            border: 1px solid #dee2e6;
            border-radius: 10px;
            transition: all 0.3s ease;
        }
        .location-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8rem;
        }
        .map-container {
            height: 400px;
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-action {
            margin: 2px;
        }
        .radius-indicator {
            position: absolute;
            border: 2px dashed #007bff;
            border-radius: 50%;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-map-marker-alt text-primary"></i> Location-Based Attendance</h2>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addLocationModal">
                        <i class="fas fa-plus"></i> Add Location
                    </button>
                </div>
            </div>
        </div>

        <!-- Settings Panel -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Location Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="locationVerificationEnabled">
                                    <label class="form-check-label" for="locationVerificationEnabled">
                                        Enable Location Verification
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="allowMultipleLocations">
                                    <label class="form-check-label" for="allowMultipleLocations">
                                        Allow Multiple Locations
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <label for="defaultRadius" class="form-label">Default Radius (meters)</label>
                                <input type="number" class="form-control" id="defaultRadius" value="100" min="10" max="1000">
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button class="btn btn-success" onclick="updateSettings()">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Locations List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Allowed Locations</h5>
                    </div>
                    <div class="card-body">
                        <div id="locationsList" class="row">
                            <!-- Locations will be loaded here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Location Modal -->
    <div class="modal fade" id="addLocationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addLocationForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="locationName" class="form-label">Location Name *</label>
                                    <input type="text" class="form-control" id="locationName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="locationRadius" class="form-label">Radius (meters) *</label>
                                    <input type="number" class="form-control" id="locationRadius" value="100" min="10" max="1000" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="locationAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="locationAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="locationDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="locationDescription" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location Coordinates</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="locationLatitude" step="any" placeholder="Latitude" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="locationLongitude" step="any" placeholder="Longitude" required>
                                </div>
                            </div>
                            <small class="text-muted">Click "Get Current Location" or enter coordinates manually</small>
                        </div>
                        <div class="mb-3">
                            <button type="button" class="btn btn-outline-primary" onclick="getCurrentLocation()">
                                <i class="fas fa-crosshairs"></i> Get Current Location
                            </button>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="addLocation()">Add Location</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Location Modal -->
    <div class="modal fade" id="editLocationModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Location</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editLocationForm">
                        <input type="hidden" id="editLocationId">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editLocationName" class="form-label">Location Name *</label>
                                    <input type="text" class="form-control" id="editLocationName" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="editLocationRadius" class="form-label">Radius (meters) *</label>
                                    <input type="number" class="form-control" id="editLocationRadius" min="10" max="1000" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="editLocationAddress" class="form-label">Address</label>
                            <textarea class="form-control" id="editLocationAddress" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editLocationDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editLocationDescription" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location Coordinates</label>
                            <div class="row">
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="editLocationLatitude" step="any" placeholder="Latitude" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="number" class="form-control" id="editLocationLongitude" step="any" placeholder="Longitude" required>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="updateLocation()">Update Location</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const branchId = <?php echo $branch_id; ?>;
        let locations = [];

        // Load settings on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadSettings();
            loadLocations();
        });

        // Load location settings
        async function loadSettings() {
            try {
                const response = await fetch(`/api/location-attendance/settings/${branchId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    const settings = data.data;
                    document.getElementById('locationVerificationEnabled').checked = settings.location_verification_enabled == 1;
                    document.getElementById('allowMultipleLocations').checked = settings.allow_multiple_locations == 1;
                    document.getElementById('defaultRadius').value = settings.default_radius || 100;
                }
            } catch (error) {
                console.error('Error loading settings:', error);
                showAlert('Error loading settings', 'danger');
            }
        }

        // Load locations
        async function loadLocations() {
            try {
                const response = await fetch(`/api/location-attendance/locations/${branchId}`);
                const data = await response.json();
                
                if (data.status === 'success') {
                    locations = data.data;
                    displayLocations();
                }
            } catch (error) {
                console.error('Error loading locations:', error);
                showAlert('Error loading locations', 'danger');
            }
        }

        // Display locations
        function displayLocations() {
            const container = document.getElementById('locationsList');
            container.innerHTML = '';

            if (locations.length === 0) {
                container.innerHTML = `
                    <div class="col-12 text-center py-4">
                        <i class="fas fa-map-marker-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No locations configured</h5>
                        <p class="text-muted">Add your first location to start using location-based attendance.</p>
                    </div>
                `;
                return;
            }

            locations.forEach(location => {
                const card = document.createElement('div');
                card.className = 'col-md-6 col-lg-4 mb-3';
                card.innerHTML = `
                    <div class="card location-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="card-title mb-0">${location.name}</h6>
                                <span class="badge ${location.is_active == 1 ? 'bg-success' : 'bg-secondary'} status-badge">
                                    ${location.is_active == 1 ? 'Active' : 'Inactive'}
                                </span>
                            </div>
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-map-marker-alt"></i> 
                                ${location.latitude}, ${location.longitude}
                            </p>
                            <p class="card-text text-muted small mb-2">
                                <i class="fas fa-circle"></i> 
                                Radius: ${location.radius}m
                            </p>
                            ${location.address ? `<p class="card-text small">${location.address}</p>` : ''}
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-primary btn-action" onclick="editLocation(${location.id})">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger btn-action" onclick="deleteLocation(${location.id})">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-info btn-action" onclick="toggleLocationStatus(${location.id}, ${location.is_active})">
                                    <i class="fas fa-power-off"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `;
                container.appendChild(card);
            });
        }

        // Get current location
        function getCurrentLocation() {
            if (!navigator.geolocation) {
                showAlert('Geolocation is not supported by this browser', 'warning');
                return;
            }

            navigator.geolocation.getCurrentPosition(
                function(position) {
                    document.getElementById('locationLatitude').value = position.coords.latitude.toFixed(8);
                    document.getElementById('locationLongitude').value = position.coords.longitude.toFixed(8);
                },
                function(error) {
                    showAlert('Error getting location: ' + error.message, 'danger');
                }
            );
        }

        // Add location
        async function addLocation() {
            const form = document.getElementById('addLocationForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const data = {
                branch_id: branchId,
                name: document.getElementById('locationName').value,
                latitude: parseFloat(document.getElementById('locationLatitude').value),
                longitude: parseFloat(document.getElementById('locationLongitude').value),
                radius: parseInt(document.getElementById('locationRadius').value),
                address: document.getElementById('locationAddress').value,
                description: document.getElementById('locationDescription').value,
                is_active: 1
            };

            try {
                const response = await fetch('/api/location-attendance/locations', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Location added successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('addLocationModal')).hide();
                    form.reset();
                    loadLocations();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error adding location:', error);
                showAlert('Error adding location', 'danger');
            }
        }

        // Edit location
        function editLocation(locationId) {
            const location = locations.find(loc => loc.id == locationId);
            if (!location) return;

            document.getElementById('editLocationId').value = location.id;
            document.getElementById('editLocationName').value = location.name;
            document.getElementById('editLocationLatitude').value = location.latitude;
            document.getElementById('editLocationLongitude').value = location.longitude;
            document.getElementById('editLocationRadius').value = location.radius;
            document.getElementById('editLocationAddress').value = location.address || '';
            document.getElementById('editLocationDescription').value = location.description || '';

            new bootstrap.Modal(document.getElementById('editLocationModal')).show();
        }

        // Update location
        async function updateLocation() {
            const form = document.getElementById('editLocationForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const locationId = document.getElementById('editLocationId').value;
            const data = {
                name: document.getElementById('editLocationName').value,
                latitude: parseFloat(document.getElementById('editLocationLatitude').value),
                longitude: parseFloat(document.getElementById('editLocationLongitude').value),
                radius: parseInt(document.getElementById('editLocationRadius').value),
                address: document.getElementById('editLocationAddress').value,
                description: document.getElementById('editLocationDescription').value
            };

            try {
                const response = await fetch(`/api/location-attendance/locations/${locationId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Location updated successfully', 'success');
                    bootstrap.Modal.getInstance(document.getElementById('editLocationModal')).hide();
                    loadLocations();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error updating location:', error);
                showAlert('Error updating location', 'danger');
            }
        }

        // Delete location
        async function deleteLocation(locationId) {
            if (!confirm('Are you sure you want to delete this location?')) return;

            try {
                const response = await fetch(`/api/location-attendance/locations/${locationId}`, {
                    method: 'DELETE'
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Location deleted successfully', 'success');
                    loadLocations();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error deleting location:', error);
                showAlert('Error deleting location', 'danger');
            }
        }

        // Toggle location status
        async function toggleLocationStatus(locationId, currentStatus) {
            const newStatus = currentStatus == 1 ? 0 : 1;
            
            try {
                const response = await fetch(`/api/location-attendance/locations/${locationId}`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ is_active: newStatus })
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert(`Location ${newStatus == 1 ? 'activated' : 'deactivated'} successfully`, 'success');
                    loadLocations();
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error updating location status:', error);
                showAlert('Error updating location status', 'danger');
            }
        }

        // Update settings
        async function updateSettings() {
            const data = {
                branch_id: branchId,
                location_verification_enabled: document.getElementById('locationVerificationEnabled').checked ? 1 : 0,
                allow_multiple_locations: document.getElementById('allowMultipleLocations').checked ? 1 : 0,
                default_radius: parseInt(document.getElementById('defaultRadius').value)
            };

            try {
                const response = await fetch('/api/location-attendance/settings', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();
                
                if (result.status === 'success') {
                    showAlert('Settings updated successfully', 'success');
                } else {
                    showAlert(result.message, 'danger');
                }
            } catch (error) {
                console.error('Error updating settings:', error);
                showAlert('Error updating settings', 'danger');
            }
        }

        // Show alert
        function showAlert(message, type) {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.insertBefore(alertDiv, document.body.firstChild);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 5000);
        }
    </script>
</body>
</html>
