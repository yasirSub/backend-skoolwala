<?php
defined('BASEPATH') OR exit('No direct script access allowed');
?>

<section class="panel">
    <div class="panel-body">
        <div class="row">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-map-marker-alt text-primary"></i> Location Settings</h4>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#addLocationModal">
                        <i class="fas fa-plus"></i> Add Location
                    </button>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    Manage attendance locations with GPS coordinates and radius settings. Staff can only mark attendance when they are within the specified radius of these locations.
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-12">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Location Name</th>
                                <th>Address</th>
                                <th>Coordinates</th>
                                <th>Radius</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($locations)): ?>
                                <?php $i = 1; foreach ($locations as $location): ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($location->name); ?></strong>
                                        <?php if (!empty($location->description)): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($location->description); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($location->address); ?></td>
                                    <td>
                                        <small>
                                            <i class="fas fa-map-pin text-danger"></i> 
                                            <?php echo number_format($location->latitude, 6); ?>, <?php echo number_format($location->longitude, 6); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <i class="fas fa-circle"></i> <?php echo $location->radius; ?>m
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($location->is_active): ?>
                                            <span class="badge badge-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <small><?php echo date('M d, Y', strtotime($location->created_at)); ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-warning edit-location-btn" 
                                                data-location='<?php echo json_encode($location); ?>'
                                                data-toggle="tooltip" title="Edit Location">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger delete-location-btn" 
                                                data-location-id="<?php echo $location->id; ?>"
                                                data-location-name="<?php echo htmlspecialchars($location->name); ?>"
                                                data-toggle="tooltip" title="Delete Location">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted">
                                        <i class="fas fa-map-marker-alt fa-3x mb-3"></i>
                                        <br>No locations configured yet. Click "Add Location" to get started.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Add Location Modal -->
<div class="modal fade" id="addLocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Location</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="addLocationForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="location_name">Location Name *</label>
                                <input type="text" class="form-control" id="location_name" name="location_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="radius">Radius (meters) *</label>
                                <input type="number" class="form-control" id="radius" name="radius" min="10" max="1000" value="100" required>
                                <small class="form-text text-muted">10m - 1000m allowed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="latitude">Latitude *</label>
                                <input type="number" class="form-control" id="latitude" name="latitude" step="any" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="longitude">Longitude *</label>
                                <input type="number" class="form-control" id="longitude" name="longitude" step="any" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="address">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn btn-info btn-sm" id="getCurrentLocation">
                                    <i class="fas fa-crosshairs"></i> Get Current Location
                                </button>
                                <small class="form-text text-muted">Click to automatically fill coordinates</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Add Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Location Modal -->
<div class="modal fade" id="editLocationModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Location</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form id="editLocationForm">
                <input type="hidden" id="edit_location_id" name="location_id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_location_name">Location Name *</label>
                                <input type="text" class="form-control" id="edit_location_name" name="location_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_radius">Radius (meters) *</label>
                                <input type="number" class="form-control" id="edit_radius" name="radius" min="10" max="1000" required>
                                <small class="form-text text-muted">10m - 1000m allowed</small>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_latitude">Latitude *</label>
                                <input type="number" class="form-control" id="edit_latitude" name="latitude" step="any" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="edit_longitude">Longitude *</label>
                                <input type="number" class="form-control" id="edit_longitude" name="longitude" step="any" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_address">Address</label>
                                <textarea class="form-control" id="edit_address" name="address" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" id="edit_description" name="description" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="edit_is_active" name="is_active" value="1">
                                    <label class="form-check-label" for="edit_is_active">
                                        Active Location
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <button type="button" class="btn btn-info btn-sm" id="editGetCurrentLocation">
                                    <i class="fas fa-crosshairs"></i> Get Current Location
                                </button>
                                <small class="form-text text-muted">Click to automatically fill coordinates</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-save"></i> Update Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Add location form submission
    $('#addLocationForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'add_location');
        
        $.ajax({
            url: '<?php echo base_url("school_settings/location_settings"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    $('#addLocationModal').modal('hide');
                    location.reload();
                } else {
                    showAlert('danger', 'Error: ' + (response.message || 'Failed to add location'));
                }
            },
            error: function() {
                showAlert('danger', 'Error occurred while adding location');
            }
        });
    });
    
    // Edit location button click
    $('.edit-location-btn').on('click', function() {
        var location = $(this).data('location');
        
        $('#edit_location_id').val(location.id);
        $('#edit_location_name').val(location.name);
        $('#edit_latitude').val(location.latitude);
        $('#edit_longitude').val(location.longitude);
        $('#edit_radius').val(location.radius);
        $('#edit_address').val(location.address);
        $('#edit_description').val(location.description);
        $('#edit_is_active').prop('checked', location.is_active == 1);
        
        $('#editLocationModal').modal('show');
    });
    
    // Edit location form submission
    $('#editLocationForm').on('submit', function(e) {
        e.preventDefault();
        
        var formData = new FormData(this);
        formData.append('action', 'edit_location');
        
        $.ajax({
            url: '<?php echo base_url("school_settings/location_settings"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.status === 'success') {
                    showAlert('success', response.message);
                    $('#editLocationModal').modal('hide');
                    location.reload();
                } else {
                    showAlert('danger', 'Error: ' + (response.message || 'Failed to update location'));
                }
            },
            error: function() {
                showAlert('danger', 'Error occurred while updating location');
            }
        });
    });
    
    // Delete location button click
    $('.delete-location-btn').on('click', function() {
        var locationId = $(this).data('location-id');
        var locationName = $(this).data('location-name');
        
        if (confirm('Are you sure you want to delete "' + locationName + '"?')) {
            $.ajax({
                url: '<?php echo base_url("school_settings/location_settings"); ?>',
                type: 'POST',
                data: {
                    action: 'delete_location',
                    location_id: locationId
                },
                success: function(response) {
                    if (response.status === 'success') {
                        showAlert('success', response.message);
                        location.reload();
                    } else {
                        showAlert('danger', 'Error: ' + (response.message || 'Failed to delete location'));
                    }
                },
                error: function() {
                    showAlert('danger', 'Error occurred while deleting location');
                }
            });
        }
    });
    
    // Get current location for add form
    $('#getCurrentLocation').on('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#latitude').val(position.coords.latitude);
                $('#longitude').val(position.coords.longitude);
                showAlert('info', 'Current location coordinates filled successfully!');
            }, function(error) {
                showAlert('danger', 'Error getting location: ' + error.message);
            });
        } else {
            showAlert('danger', 'Geolocation is not supported by this browser.');
        }
    });
    
    // Get current location for edit form
    $('#editGetCurrentLocation').on('click', function() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                $('#edit_latitude').val(position.coords.latitude);
                $('#edit_longitude').val(position.coords.longitude);
                showAlert('info', 'Current location coordinates filled successfully!');
            }, function(error) {
                showAlert('danger', 'Error getting location: ' + error.message);
            });
        } else {
            showAlert('danger', 'Geolocation is not supported by this browser.');
        }
    });
    
    // Show alert function
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
                       '<i class="fas fa-info-circle"></i> ' + message +
                       '<button type="button" class="close" data-dismiss="alert">' +
                       '<span>&times;</span></button></div>';
        
        $('.panel-body').prepend(alertHtml);
        
        // Auto remove after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    }
});
</script>
