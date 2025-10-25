<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location-Based Attendance Analytics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .analytics-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #2c3e50;
        }
        .metric-label {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        .chart-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .data-table {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: bold;
        }
        .status-present { background: #d4edda; color: #155724; }
        .status-absent { background: #f8d7da; color: #721c24; }
        .verified-yes { background: #d1ecf1; color: #0c5460; }
        .verified-no { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="analytics-card">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2><i class="fas fa-chart-line"></i> Location-Based Attendance Analytics</h2>
                    <p class="mb-0">Comprehensive analytics for face recognition and location verification</p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="btn-group">
                        <button class="btn btn-light" onclick="refreshAnalytics()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                        <button class="btn btn-light" onclick="exportData()">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Date Range Filter -->
        <div class="metric-card">
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="startDate" class="form-control" value="<?php echo date('Y-m-01'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">End Date</label>
                    <input type="date" id="endDate" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Staff Member</label>
                    <select id="staffSelect" class="form-select">
                        <option value="">All Staff</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="loadAnalytics()">
                        <i class="fas fa-search"></i> Load Analytics
                    </button>
                </div>
            </div>
        </div>

        <!-- Summary Metrics -->
        <div class="row" id="summaryMetrics">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Charts Row -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-map-marker-alt"></i> Location Usage</h5>
                    <canvas id="locationChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-user-check"></i> Verification Rates</h5>
                    <canvas id="verificationChart"></canvas>
                </div>
            </div>
        </div>

        <!-- GPS Analytics -->
        <div class="row">
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-crosshairs"></i> GPS Distance Distribution</h5>
                    <canvas id="distanceChart"></canvas>
                </div>
            </div>
            <div class="col-md-6">
                <div class="chart-container">
                    <h5><i class="fas fa-clock"></i> Check-in Time Distribution</h5>
                    <canvas id="timeChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Detailed Data Table -->
        <div class="data-table">
            <h5><i class="fas fa-table"></i> Detailed Attendance Records</h5>
            <div class="table-responsive">
                <table class="table table-striped" id="attendanceTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Staff</th>
                            <th>Status</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Location</th>
                            <th>GPS Coordinates</th>
                            <th>Distance (m)</th>
                            <th>Face Verified</th>
                            <th>Location Verified</th>
                            <th>Remark</th>
                        </tr>
                    </thead>
                    <tbody id="attendanceTableBody">
                        <!-- Will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let analyticsData = null;
        let locationChart = null;
        let verificationChart = null;
        let distanceChart = null;
        let timeChart = null;

        // Load analytics on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadAnalytics();
        });

        function loadAnalytics() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const staffId = document.getElementById('staffSelect').value;

            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate
            });
            
            if (staffId) {
                params.append('staff_id', staffId);
            }

            fetch(`/api/locationAnalytics?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        analyticsData = data.data;
                        updateSummaryMetrics();
                        updateCharts();
                        updateTable();
                    } else {
                        alert('Error loading analytics: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading analytics');
                });
        }

        function updateSummaryMetrics() {
            const summary = analyticsData.summary;
            const metricsHtml = `
                <div class="col-md-3">
                    <div class="metric-card text-center">
                        <div class="metric-value">${summary.total_attendance_records}</div>
                        <div class="metric-label">Total Records</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card text-center">
                        <div class="metric-value">${summary.present_count}</div>
                        <div class="metric-label">Present</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card text-center">
                        <div class="metric-value">${summary.location_verification_rate}%</div>
                        <div class="metric-label">Location Verified</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="metric-card text-center">
                        <div class="metric-value">${summary.face_verification_rate}%</div>
                        <div class="metric-label">Face Verified</div>
                    </div>
                </div>
            `;
            document.getElementById('summaryMetrics').innerHTML = metricsHtml;
        }

        function updateCharts() {
            updateLocationChart();
            updateVerificationChart();
            updateDistanceChart();
            updateTimeChart();
        }

        function updateLocationChart() {
            const ctx = document.getElementById('locationChart').getContext('2d');
            
            if (locationChart) {
                locationChart.destroy();
            }

            const locationData = analyticsData.location_data;
            locationChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: locationData.map(loc => loc.location_name || 'Unknown Location'),
                    datasets: [{
                        data: locationData.map(loc => loc.attendance_count),
                        backgroundColor: [
                            '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }

        function updateVerificationChart() {
            const ctx = document.getElementById('verificationChart').getContext('2d');
            
            if (verificationChart) {
                verificationChart.destroy();
            }

            const summary = analyticsData.summary;
            verificationChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Location Verified', 'GPS Verified', 'Face Verified'],
                    datasets: [{
                        label: 'Verification Rate (%)',
                        data: [
                            summary.location_verification_rate,
                            summary.gps_verification_rate,
                            summary.face_verification_rate
                        ],
                        backgroundColor: ['#36A2EB', '#4BC0C0', '#FFCE56']
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100
                        }
                    }
                }
            });
        }

        function updateDistanceChart() {
            const ctx = document.getElementById('distanceChart').getContext('2d');
            
            if (distanceChart) {
                distanceChart.destroy();
            }

            const locationData = analyticsData.location_data;
            distanceChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: locationData.map(loc => loc.location_name || 'Unknown'),
                    datasets: [{
                        label: 'Average Distance (meters)',
                        data: locationData.map(loc => Math.round(loc.avg_distance_from_school || 0)),
                        backgroundColor: '#FF6384'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function updateTimeChart() {
            const ctx = document.getElementById('timeChart').getContext('2d');
            
            if (timeChart) {
                timeChart.destroy();
            }

            const timeData = analyticsData.time_analytics;
            timeChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: timeData.map(t => t.staff_name),
                    datasets: [{
                        label: 'Average Check-in Time',
                        data: timeData.map(t => t.avg_check_in_time || '00:00:00'),
                        borderColor: '#36A2EB',
                        backgroundColor: 'rgba(54, 162, 235, 0.1)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            type: 'time',
                            time: {
                                unit: 'hour'
                            }
                        }
                    }
                }
            });
        }

        function updateTable() {
            const tbody = document.getElementById('attendanceTableBody');
            const dailyData = analyticsData.daily_breakdown;
            
            tbody.innerHTML = dailyData.map(record => `
                <tr>
                    <td>${record.date}</td>
                    <td>${record.staff_name}</td>
                    <td><span class="status-badge status-${record.status === 'P' ? 'present' : 'absent'}">${record.status === 'P' ? 'Present' : 'Absent'}</span></td>
                    <td>${record.in_time || '-'}</td>
                    <td>${record.out_time || '-'}</td>
                    <td>${record.location_name || '-'}</td>
                    <td>${record.user_latitude && record.user_longitude ? `${record.user_latitude.toFixed(6)}, ${record.user_longitude.toFixed(6)}` : '-'}</td>
                    <td>${record.distance_from_school ? Math.round(record.distance_from_school) + 'm' : '-'}</td>
                    <td><span class="status-badge ${record.face_verified ? 'verified-yes' : 'verified-no'}">${record.face_verified ? 'Yes' : 'No'}</span></td>
                    <td><span class="status-badge ${record.location_verified ? 'verified-yes' : 'verified-no'}">${record.location_verified ? 'Yes' : 'No'}</span></td>
                    <td>${record.remark || '-'}</td>
                </tr>
            `).join('');
        }

        function refreshAnalytics() {
            loadAnalytics();
        }

        function exportData() {
            const startDate = document.getElementById('startDate').value;
            const endDate = document.getElementById('endDate').value;
            const staffId = document.getElementById('staffSelect').value;

            const params = new URLSearchParams({
                start_date: startDate,
                end_date: endDate
            });
            
            if (staffId) {
                params.append('staff_id', staffId);
            }

            window.open(`/api/exportLocationAnalytics?${params}`, '_blank');
        }
    </script>
</body>
</html>
