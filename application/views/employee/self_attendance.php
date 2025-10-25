<div class="row">
    <div class="col-md-12">
        <!-- Filter Section -->
        <section class="panel">
            <?php echo form_open($this->uri->uri_string()); ?>
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-filter"></i> Filter Attendance Records</h4>
            </header>
            <div class="panel-body">
                <div class="row mb-sm">
                    <div class="col-md-4 mb-sm">
                        <div class="form-group">
                            <label class="control-label">Filter Type <span class="required">*</span></label>
                            <select name="filter_type" id="filter_type" class="form-control" onchange="toggleFilterInput()">
                                <option value="month" <?php echo ($filter_type == 'month') ? 'selected' : ''; ?>>By Month</option>
                                <option value="daterange" <?php echo ($filter_type == 'daterange') ? 'selected' : ''; ?>>By Date Range</option>
                                <option value="year" <?php echo ($filter_type == 'year') ? 'selected' : ''; ?>>By Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4 mb-sm">
                        <div class="form-group">
                            <label class="control-label">Filter Value <span class="required">*</span></label>
                            <div id="month_input" style="<?php echo ($filter_type == 'month') ? '' : 'display:none;'; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="filter_value" id="month_picker" 
                                           value="<?php echo ($filter_type == 'month') ? $filter_value : date('Y-m'); ?>" 
                                           data-plugin-datepicker required
                                           data-plugin-options='{ "format": "yyyy-MM", "minViewMode": "months", "orientation": "bottom"}' />
                                    <span class="input-group-addon"><i class="fas fa-calendar"></i></span>
                                </div>
                            </div>
                            <div id="daterange_input" style="<?php echo ($filter_type == 'daterange') ? '' : 'display:none;'; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="filter_value" id="daterange_picker" 
                                           value="<?php echo ($filter_type == 'daterange') ? $filter_value : ''; ?>" 
                                           placeholder="Select date range" />
                                    <span class="input-group-addon"><i class="fas fa-calendar"></i></span>
                                </div>
                            </div>
                            <div id="year_input" style="<?php echo ($filter_type == 'year') ? '' : 'display:none;'; ?>">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="filter_value" id="year_picker" 
                                           value="<?php echo ($filter_type == 'year') ? $filter_value : date('Y'); ?>" 
                                           data-plugin-datepicker required
                                           data-plugin-options='{ "format": "yyyy", "minViewMode": "years", "orientation": "bottom"}' />
                                    <span class="input-group-addon"><i class="fas fa-calendar"></i></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-sm">
                        <div class="form-group">
                            <label class="control-label">&nbsp;</label>
                            <button type="submit" name="search" value="1" class="btn btn-primary btn-block">
                                <i class="fas fa-filter"></i> Apply Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php echo form_close(); ?>
        </section>

        <!-- Attendance Records Section -->
        <section class="panel">
            <header class="panel-heading">
                <h4 class="panel-title"><i class="fas fa-user-clock"></i> Self Attendance Records</h4>
            </header>
            <div class="panel-body">
                <!-- Staff Information Card -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-user"></i> Your Information</h5>
                            <div class="row">
                                <div class="col-md-3">
                                    <strong>Name:</strong> <?php echo html_escape($staff_info->name); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Staff ID:</strong> <?php echo html_escape($staff_info->staff_id); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Designation:</strong> <?php echo html_escape($staff_info->designation_name); ?>
                                </div>
                                <div class="col-md-3">
                                    <strong>Department:</strong> <?php echo html_escape($staff_info->department_name); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Summary -->
                <div class="row mt-md">
                    <div class="col-md-3 col-sm-6">
                        <div class="panel panel-success">
                            <div class="panel-body">
                                <div class="widget-summary">
                                    <div class="widget-summary-col widget-summary-col-icon">
                                        <div class="summary-icon bg-success">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="widget-summary-col">
                                        <div class="summary">
                                            <h4 class="title">Present Days</h4>
                                            <div class="info">
                                                <strong class="amount"><?php echo $present_days; ?></strong>
                                                <span class="text-success">(<?php echo number_format($present_percentage, 2); ?>%)</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="panel panel-danger">
                            <div class="panel-body">
                                <div class="widget-summary">
                                    <div class="widget-summary-col widget-summary-col-icon">
                                        <div class="summary-icon bg-danger">
                                            <i class="fas fa-times"></i>
                                        </div>
                                    </div>
                                    <div class="widget-summary-col">
                                        <div class="summary">
                                            <h4 class="title">Absent Days</h4>
                                            <div class="info">
                                                <strong class="amount"><?php echo $absent_days; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="panel panel-warning">
                            <div class="panel-body">
                                <div class="widget-summary">
                                    <div class="widget-summary-col widget-summary-col-icon">
                                        <div class="summary-icon bg-warning">
                                            <i class="fas fa-star-half-alt"></i>
                                        </div>
                                    </div>
                                    <div class="widget-summary-col">
                                        <div class="summary">
                                            <h4 class="title">Half Days</h4>
                                            <div class="info">
                                                <strong class="amount"><?php echo $half_days; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6">
                        <div class="panel panel-info">
                            <div class="panel-body">
                                <div class="widget-summary">
                                    <div class="widget-summary-col widget-summary-col-icon">
                                        <div class="summary-icon bg-info">
                                            <i class="fas fa-clock"></i>
                                        </div>
                                    </div>
                                    <div class="widget-summary-col">
                                        <div class="summary">
                                            <h4 class="title">Late Days</h4>
                                            <div class="info">
                                                <strong class="amount"><?php echo $late_days; ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance Records -->
                <div class="row">
                    <div class="col-md-12">
                        <h5><i class="fas fa-calendar-check"></i> Your Attendance Records 
                            <?php if ($filter_type == 'month'): ?>
                                (<?php echo date('F Y', strtotime($filter_value . '-01')); ?>)
                            <?php elseif ($filter_type == 'daterange'): ?>
                                (<?php echo $filter_value; ?>)
                            <?php elseif ($filter_type == 'year'): ?>
                                (<?php echo $filter_value; ?>)
                            <?php else: ?>
                                (Current Month)
                            <?php endif; ?>
                        </h5>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-condensed" id="selfAttendanceTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Date</th>
                                        <th>Day</th>
                                        <th>Status</th>
                                        <th>Check In</th>
                                        <th>Check Out</th>
                                        <th>Working Hours</th>
                                        <th>Check In Location</th>
                                        <th>Check Out Location</th>
                                        <th>Method</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($attendance_records)): ?>
                                        <?php $count = 1; foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo $count++; ?></td>
                                                <td><?php echo _d($record->date); ?></td>
                                                <td><?php echo date('l', strtotime($record->date)); ?></td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    $status_text = '';
                                                    switch($record->status) {
                                                        case 'P':
                                                            $status_class = 'label-success';
                                                            $status_text = 'Present';
                                                            break;
                                                        case 'A':
                                                            $status_class = 'label-danger';
                                                            $status_text = 'Absent';
                                                            break;
                                                        case 'H':
                                                            $status_class = 'label-warning';
                                                            $status_text = 'Half Day';
                                                            break;
                                                        case 'L':
                                                            $status_class = 'label-info';
                                                            $status_text = 'Late';
                                                            break;
                                                        default:
                                                            $status_class = 'label-default';
                                                            $status_text = 'Unknown';
                                                    }
                                                    ?>
                                                    <span class="label <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($record->in_time)): ?>
                                                        <?php echo $record->in_time; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($record->out_time)): ?>
                                                        <?php echo $record->out_time; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($record->in_time) && !empty($record->out_time)): ?>
                                                        <?php 
                                                        $check_in = strtotime($record->in_time);
                                                        $check_out = strtotime($record->out_time);
                                                        $working_hours = ($check_out - $check_in) / 3600;
                                                        echo number_format($working_hours, 2) . ' hrs';
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Check In Location -->
                                                <td>
                                                    <?php if (!empty($record->user_latitude) && !empty($record->user_longitude) && !empty($record->in_time)): ?>
                                                        <?php
                                                        // Get school location
                                                        $school_query = $this->db->query("SELECT latitude, longitude, name FROM school_locations WHERE is_active = 1 ORDER BY id LIMIT 1");
                                                        $school_location = $school_query->row();
                                                        
                                                        if ($school_location) {
                                                            // Calculate distance using Haversine formula
                                                            $earth_radius = 6371000; // Earth's radius in meters
                                                            $lat1_rad = deg2rad($record->user_latitude);
                                                            $lon1_rad = deg2rad($record->user_longitude);
                                                            $lat2_rad = deg2rad($school_location->latitude);
                                                            $lon2_rad = deg2rad($school_location->longitude);
                                                            $delta_lat = $lat2_rad - $lat1_rad;
                                                            $delta_lon = $lon2_rad - $lon1_rad;
                                                            $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
                                                                 cos($lat1_rad) * cos($lat2_rad) * sin($delta_lon / 2) * sin($delta_lon / 2);
                                                            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                                                            $distance = $earth_radius * $c;
                                                            $display_distance = $distance >= 1000 ? number_format($distance / 1000, 2) . ' km' : round($distance) . ' m';
                                                            echo '<span class="label label-success" title="Check-in distance from ' . $school_location->name . '">📍 ' . $display_distance . '</span>';
                                                        } else {
                                                            echo '<span class="label label-info">GPS</span>';
                                                        }
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <!-- Check Out Location -->
                                                <td>
                                                    <?php if (!empty($record->user_latitude) && !empty($record->user_longitude) && !empty($record->out_time)): ?>
                                                        <?php
                                                        // Get school location
                                                        $school_query = $this->db->query("SELECT latitude, longitude, name FROM school_locations WHERE is_active = 1 ORDER BY id LIMIT 1");
                                                        $school_location = $school_query->row();
                                                        
                                                        if ($school_location) {
                                                            // Calculate distance using Haversine formula
                                                            $earth_radius = 6371000; // Earth's radius in meters
                                                            $lat1_rad = deg2rad($record->user_latitude);
                                                            $lon1_rad = deg2rad($record->user_longitude);
                                                            $lat2_rad = deg2rad($school_location->latitude);
                                                            $lon2_rad = deg2rad($school_location->longitude);
                                                            $delta_lat = $lat2_rad - $lat1_rad;
                                                            $delta_lon = $lon2_rad - $lon1_rad;
                                                            $a = sin($delta_lat / 2) * sin($delta_lat / 2) +
                                                                 cos($lat1_rad) * cos($lat2_rad) * sin($delta_lon / 2) * sin($delta_lon / 2);
                                                            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                                                            $distance = $earth_radius * $c;
                                                            $display_distance = $distance >= 1000 ? number_format($distance / 1000, 2) . ' km' : round($distance) . ' m';
                                                            echo '<span class="label label-success" title="Check-out distance from ' . $school_location->name . '">📍 ' . $display_distance . '</span>';
                                                        } else {
                                                            echo '<span class="label label-info">GPS</span>';
                                                        }
                                                        ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $method_icons = '';
                                                    if (isset($record->qr_code) && $record->qr_code == 1) {
                                                        $method_icons .= '<i class="fas fa-qrcode" title="QR Code"></i> ';
                                                    }
                                                    if (isset($record->face_data) && !empty($record->face_data)) {
                                                        $method_icons .= '<i class="fas fa-user-check" title="Face Recognition"></i> ';
                                                    }
                                                    if (empty($method_icons)) {
                                                        $method_icons = '<i class="fas fa-hand-paper" title="Manual"></i>';
                                                    }
                                                    echo $method_icons;
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($record->remark)): ?>
                                                        <?php echo html_escape($record->remark); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="10" class="text-center">
                                                <i class="fas fa-calendar-times fa-2x text-muted"></i><br>
                                                <span class="text-muted">No attendance records found.</span>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Summary Statistics -->
                <?php if (!empty($attendance_records)): ?>
                <div class="row">
                    <div class="col-md-12">
                        <h5><i class="fas fa-chart-pie"></i> Attendance Summary</h5>
                        <div class="row">
                            <?php 
                            $total_days = count($attendance_records);
                            $present_days = 0;
                            $absent_days = 0;
                            $half_days = 0;
                            $late_days = 0;
                            
                            foreach ($attendance_records as $record) {
                                switch($record->status) {
                                    case 'P':
                                        $present_days++;
                                        break;
                                    case 'A':
                                        $absent_days++;
                                        break;
                                    case 'H':
                                        $half_days++;
                                        break;
                                    case 'L':
                                        $late_days++;
                                        break;
                                }
                            }
                            
                            $present_percentage = $total_days > 0 ? round(($present_days / $total_days) * 100, 1) : 0;
                            ?>
                            <div class="col-md-3">
                                <div class="panel panel-success">
                                    <div class="panel-body text-center">
                                        <h3 class="text-success"><?php echo $present_days; ?></h3>
                                        <p>Present Days</p>
                                        <small><?php echo $present_percentage; ?>%</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-danger">
                                    <div class="panel-body text-center">
                                        <h3 class="text-danger"><?php echo $absent_days; ?></h3>
                                        <p>Absent Days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-warning">
                                    <div class="panel-body text-center">
                                        <h3 class="text-warning"><?php echo $half_days; ?></h3>
                                        <p>Half Days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="panel panel-info">
                                    <div class="panel-body text-center">
                                        <h3 class="text-info"><?php echo $late_days; ?></h3>
                                        <p>Late Days</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script>
// Function to toggle filter input based on selected type
function toggleFilterInput() {
    var filterType = document.getElementById('filter_type').value;
    
    // Hide all inputs
    document.getElementById('month_input').style.display = 'none';
    document.getElementById('daterange_input').style.display = 'none';
    document.getElementById('year_input').style.display = 'none';
    
    // Show the selected input
    if (filterType === 'month') {
        document.getElementById('month_input').style.display = 'block';
    } else if (filterType === 'daterange') {
        document.getElementById('daterange_input').style.display = 'block';
    } else if (filterType === 'year') {
        document.getElementById('year_input').style.display = 'block';
    }
}

// Initialize date pickers
$(document).ready(function() {
    // Initialize month picker
    $('#month_picker').datepicker({
        format: 'yyyy-mm',
        minViewMode: 'months',
        orientation: 'bottom'
    });
    
    // Initialize year picker
    $('#year_picker').datepicker({
        format: 'yyyy',
        minViewMode: 'years',
        orientation: 'bottom'
    });
    
    // Initialize date range picker
    $('#daterange_picker').daterangepicker({
        autoUpdateInput: false,
        locale: {
            cancelLabel: 'Clear',
            format: 'YYYY-MM-DD'
        }
    });
    
    $('#daterange_picker').on('apply.daterangepicker', function(ev, picker) {
        $(this).val(picker.startDate.format('YYYY-MM-DD') + ' to ' + picker.endDate.format('YYYY-MM-DD'));
    });
    
    $('#daterange_picker').on('cancel.daterangepicker', function(ev, picker) {
        $(this).val('');
    });
    
    // Initialize the filter input visibility
    toggleFilterInput();
    
    // Initialize DataTable for better table functionality
    $('#selfAttendanceTable').DataTable({
        "pageLength": 25,
        "order": [[ 1, "desc" ]], // Sort by date descending
        "columnDefs": [
            { "orderable": false, "targets": [0, 7, 8] } // Disable sorting for #, Location, Method columns
        ],
        "language": {
            "search": "Search attendance records:",
            "lengthMenu": "Show _MENU_ records per page",
            "info": "Showing _START_ to _END_ of _TOTAL_ records",
            "infoEmpty": "No records found",
            "infoFiltered": "(filtered from _MAX_ total records)"
        }
    });
});
</script>
