@extends('layouts.app')

@section('content')
<style>
/* .table th:first-child,
.table td:first-child {
  position: relative;
  background-color: #f8f9fa;
} */
</style>
<div class="container-fluid px-4">
    <div class="row justify-content-start">
        @include('layouts.left-menu')
        <div class="col-xs-11 col-sm-11 col-md-11 col-lg-10 col-xl-10 col-xxl-10">
            <div class="row pt-2">
                <div class="col ps-4">
                    <h1 class="display-6 mb-3">
                        <i class="bi bi-person-lines-fill"></i> Student
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                          <li class="breadcrumb-item"><a href="{{route('home')}}">Home</a></li>
                          <li class="breadcrumb-item"><a href="{{route('student.list.show')}}">Student List</a></li>
                          <li class="breadcrumb-item active" aria-current="page">Profile</li>
                        </ol>
                    </nav>
                    <div class="mb-4">
                        <div class="row">
                            <div class="col-sm-4 col-md-3">
                                <div class="card bg-light">
                                    <div class="px-5 pt-2">
                                        @if (isset($student->photo))
                                            <img src="{{asset('/storage'.$student->photo)}}" class="rounded-3 card-img-top" alt="Profile photo">
                                        @else
                                            <img src="{{asset('imgs/profile.png')}}" class="rounded-3 card-img-top" alt="Profile photo">
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <h5 class="card-title">{{$student->first_name}} {{$student->last_name}}</h5>
                                        <p class="card-text">#ID: {{$promotion_info->id_card_number ?? 'N/A'}}</p>
                                    </div>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item">Gender: {{$student->gender}}</li>
                                        <li class="list-group-item">Phone: {{$student->phone}}</li>
                                        {{-- <li class="list-group-item"><a href="#">View Marks &amp; Results</a></li> --}}
                                    </ul>
                                </div>
                            </div>
                            <div class="col-sm-8 col-md-9">
                                <div class="p-3 mb-3 border rounded bg-white">
                                    <h6>Student Information</h6>
                                    <table class="table table-responsive mt-3">
                                        <tbody>
                                            <tr>
                                                <th scope="row">First Name:</th>
                                                <td>{{$student->first_name}}</td>
                                                <th>Last Name:</th>
                                                <td>{{$student->last_name}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Email:</th>
                                                <td>{{$student->email}}</td>
                                                <th>Birthday:</th>
                                                <td>{{$student->birthday}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Nationality:</th>
                                                <td>{{$student->nationality}}</td>
                                                <th>Religion:</th>
                                                <td>{{$student->religion}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Address:</th>
                                                <td>{{$student->address}}</td>
                                                <th>Address2:</th>
                                                <td>{{$student->address2}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">City:</th>
                                                <td>{{$student->city}}</td>
                                                <th>Zip:</th>
                                                <td>{{$student->zip}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Blood Type:</th>
                                                <td>{{$student->blood_type}}</td>
                                                <th>Phone:</th>
                                                <td>{{$student->phone}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Gender:</th>
                                                <td colspan="3">{{$student->gender}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-3 mb-3 border rounded bg-white">
                                    <h6>Parents' Information</h6>
                                    <table class="table table-responsive mt-3">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Father's Name:</th>
                                                <td>{{$student->parent_info->father_name ?? 'N/A'}}</td>
                                                <th>Mother's Name:</th>
                                                <td>{{$student->parent_info->mother_name ?? 'N/A'}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Father's Phone:</th>
                                                <td>{{$student->parent_info->father_phone ?? 'N/A'}}</td>
                                                <th>Mother's Phone:</th>
                                                <td>{{$student->parent_info->mother_phone ?? 'N/A'}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Address:</th>
                                                <td colspan="3">{{$student->parent_info->parent_address ?? 'N/A'}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="p-3 mb-3 border rounded bg-white">
                                    <h6>Academic Information</h6>
                                    <table class="table table-responsive mt-3">
                                        <tbody>
                                            <tr>
                                                <th scope="row">Class:</th>
                                                <td>{{$promotion_info->section->schoolClass->class_name ?? 'N/A'}}</td>
                                                <th>Board Reg. No.:</th>
                                                <td>{{$student->academic_info->board_reg_no ?? 'N/A'}}</td>
                                            </tr>
                                            <tr>
                                                <th scope="row">Section:</th>
                                                <td colspan="3">{{$promotion_info->section->section_name ?? 'N/A'}}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                @php
                                    $biometricEnabled = app(\App\Services\SettingService::class)->get('biometric_attendance_enabled', '1') == '1';
                                    $mappings = \App\Models\BiometricDeviceUserMapping::with('device')->where('student_id', $student->id)->get();
                                    $activeDevices = \App\Models\BiometricDevice::active()->get();
                                @endphp

                                @if($biometricEnabled)
                                <div class="p-3 mb-3 border rounded bg-white">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h6 class="mb-0"><i class="bi bi-fingerprint text-primary me-1"></i> Biometric Device Mappings</h6>
                                        @if(Auth::user()->isAdminOrSuperAdmin() && $activeDevices->count() > 0)
                                        <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                                            <i class="bi bi-plus-lg me-1"></i> Enroll on Terminal
                                        </button>
                                        @endif
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover align-middle mb-0">
                                            <thead class="table-light">
                                                <tr class="small text-muted text-uppercase">
                                                    <th>Device</th>
                                                    <th>Location</th>
                                                    <th>Machine User ID</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($mappings as $map)
                                                <tr>
                                                    <td class="fw-semibold text-dark">{{ $map->device->name ?? 'Device' }}</td>
                                                    <td class="text-muted">{{ $map->device->location ?? 'Main Entrance' }}</td>
                                                    <td><span class="badge bg-light text-primary border font-monospace">#{{ $map->device_user_id }}</span></td>
                                                    <td>
                                                        <span class="badge {{ $map->enrollment_status === 'enrolled' ? 'bg-success' : 'bg-warning text-dark' }}">
                                                            {{ ucfirst($map->enrollment_status) }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="4" class="text-center py-3 text-muted small">Not enrolled on any biometric terminals yet.</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Enroll Modal in Profile -->
                                @if(Auth::user()->isAdminOrSuperAdmin() && $activeDevices->count() > 0)
                                <div class="modal fade" id="enrollStudentModal" tabindex="-1">
                                    <div class="modal-dialog">
                                        <form method="POST" action="{{ route('biometric.enrollment.enroll') }}" class="modal-content">
                                            @csrf
                                            <input type="hidden" name="student_id" value="{{ $student->id }}">
                                            <div class="modal-header">
                                                <h6 class="modal-title fw-bold">Enroll {{ $student->first_name }} to Machine</h6>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Target Terminal</label>
                                                    <select name="device_id" class="form-select" required>
                                                        @foreach($activeDevices as $dev)
                                                        <option value="{{ $dev->id }}">{{ $dev->name }} ({{ $dev->ip_address }})</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label small fw-semibold">Machine User ID</label>
                                                    <input type="number" name="device_user_id" class="form-control" value="{{ $student->id }}" required>
                                                </div>
                                                <div class="row g-2 mb-3">
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">RFID Card No</label>
                                                        <input type="number" name="card_number" class="form-control" placeholder="Optional">
                                                    </div>
                                                    <div class="col-6">
                                                        <label class="form-label small fw-semibold">Device PIN</label>
                                                        <input type="number" name="user_password" class="form-control" value="1234">
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-cloud-upload me-1"></i> Register on Device</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                @endif
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @include('layouts.footer')
        </div>
    </div>
</div>
@endsection
