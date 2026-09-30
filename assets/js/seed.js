/* ==========================================================================
   BEACON OSAS Portal — seed dataset
   Every module page renders from these records through OSAS.store.
   Dates are stored as ISO strings (local time) and formatted by OSAS.fmt.
   ========================================================================== */
(function (window) {
  'use strict';

  /* ---------- Demo dataset metadata ---------- */
  var META = {
    system: 'BEACON',
    systemFull: 'Information Broadcasting & Student Services System',
    institution: 'Southern College of Technology',
    campusCode: 'SCT',
    academicYear: 'AY 2026-2027',
    schemaVersion: 5,
    seededAt: '2026-09-16T09:45:00',
    demoPassword: 'Beacon@2026'
  };

  /* ---------- Institutional headline metrics ----------
     Sourced from the Registrar student masterlist + portal telemetry reads.
     These are university-wide figures, not the size of the local directory. */
  var METRICS = {
    registeredStudents: 14280,
    registeredStudentsGrowth: 10.5,
    activeStudentAccounts: 13850,
    pendingActivations: 68,
    administrativeStaff: 42,
    orientationCompletionRate: 89.2,
    orientationCompletionGrowth: 3.2,
    avgSupportResponseHours: 1.4,
    portalUptime: 99.8,
    totalPortalViews: 68640,
    uniqueVisitors: 18412,
    freshmenEnrolled: 1240,
    lastSyncLabel: 'Synced 12 mins ago'
  };

  /* ---------- Weekly traffic & module engagement (dashboard chart) ---------- */
  var TRAFFIC = {
    labels: ['MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT', 'SUN'],
    axisMax: 600,
    axisStep: 100,
    current: {
      label: 'Current Week',
      logins: [204, 238, 286, 342, 398, 436, 518],
      completions: [142, 186, 204, 246, 288, 332, 398]
    },
    previous: {
      label: 'Last Week',
      logins: [176, 204, 248, 296, 330, 392, 468],
      completions: [122, 150, 178, 214, 246, 286, 352]
    }
  };

  /* ---------- Select options used by filters & forms ---------- */
  var OPTIONS = {
    roles: ['Administrator', 'Faculty', 'Staff', 'Student'],
    accountTiers: ['Student Tier', 'Faculty Tier', 'Staff Tier', 'Admin Tier'],
    years: ['First Year', 'Second Year', 'Third Year', 'Fourth Year', 'Graduate'],
    accessLevels: [
      'Administrator — Full System Access',
      'Administrator — Broadcast Management',
      'Faculty — Faculty Portal Access',
      'Staff — Records Management Access',
      'Staff — Case Records Access',
      'Student — Portal Access'
    ],
    departments: [
      'Office of Student Affairs & Services',
      'Information Systems Office',
      'Registrar\'s Office',
      "Dean's Office",
      'Guidance & Testing Center',
      'Office of the President'
    ],
    programs: [
      'BS Information Technology',
      'BS Information Systems',
      'BS Accountancy',
      'BS Management Accounting',
      'BS Civil Engineering',
      'BS Nursing',
      'AB Communication',
      'BS Criminology',
      'BS Hospitality Management'
    ],
    announcementCategories: [
      'Registrar & Enrollment',
      'Finance & Accounting',
      'System & IT Services',
      'Student Affairs',
      'Academic Affairs',
      'Health & Medical Services',
      'Library Services',
      'Scholarship & Financial Assistance'
    ],
    regulationCategories: [
      'Conduct & Discipline',
      'Academic Policies',
      'Student Organizations',
      'Campus Facilities',
      'Data Privacy & Records',
      'Health & Safety'
    ],
    moduleCategories: [
      'Orientation Module — Overview',
      'Orientation Module — Student Code of Conduct',
      'Orientation Module — Campus Facilities & Services',
      'Orientation Module — Academic Policies & Procedures',
      'Orientation Module — Student Support Services',
      'Orientation Module — Co-Curricular Programs',
      'Orientation Module — Digital Portal Guide',
      'Orientation Module — Health & Wellness'
    ],
    eventCategories: [
      'Orientation & Onboarding',
      'Student Organizations',
      'Guidance & Counseling',
      'Sports & Athletics',
      'Career & Placement',
      'Leadership Development',
      'Community Extension'
    ],
    ticketCategories: [
      'LMS Access Issues',
      'Student ID Verification',
      'Orientation Content Feedback',
      'Account & Portal Access',
      'Enrollment & Records',
      'Facilities & Services'
    ],
    audiences: [
      'All Student Portals',
      'Incoming Freshmen',
      'Graduating Students',
      'Student Officers',
      'Faculty & Staff',
      'Select Colleges'
    ],
    priorities: ['High', 'Medium', 'Low'],
    ticketStatuses: ['Open', 'In Progress', 'Resolved', 'Closed'],
    statuses: ['Published', 'Scheduled', 'Draft', 'Archived'],
    broadcastStatuses: ['Sent', 'Scheduled', 'Pending', 'Draft'],
    channels: ['Portal Notification', 'University Email', 'SMS Alert', 'Portal + Email'],
    reportCategories: [
      'User Management',
      'Announcements',
      'Orientation Content',
      'Feedback & Support',
      'Viewing & Tracking',
      'Rules & Regulations',
      'Events & Activities'
    ],
    reportFormats: ['PDF', 'Excel (XLSX)', 'CSV'],
    reportPeriods: ['September 2026', 'August 2026', 'Third Quarter 2026', 'AY 2026-2027']
  };

  window.OSAS_SEED = {
    meta: META,
    metrics: METRICS,
    traffic: TRAFFIC,
    options: OPTIONS,
    users: [],
    announcements: [],
    regulations: [],
    modules: [],
    events: [],
    tickets: [],
    broadcasts: [],
    reports: [],
    tracking: [],
    auditLog: []
  };
  window.OSAS_SEED._partial = true;

  /* ---------- Module 9: User Account & Access Management ---------- */
  /* u(first, last, role, studentId, email, dept, program, access, status, created, lastLogin, phone, extra)
     `extra` carries the verification-queue fields (year, accountTier, requestedAt, idImage).
     Ids are issued by a counter — deriving them from the array length gave every row the
     same id while the array literal was still being evaluated. */
  var TIER_BY_ROLE = {
    Administrator: 'Admin Tier', Faculty: 'Faculty Tier',
    Staff: 'Staff Tier', Student: 'Student Tier'
  };
  var userSeq = 1000;
  function u(first, last, role, studentId, email, dept, program, access, status, created, lastLogin, phone, extra) {
    var slug = email.split('@')[0];
    var meta = extra || {};
    userSeq += 1;
    return {
      id: 'USR-' + String(userSeq),
      firstName: first, lastName: last, role: role,
      studentId: studentId || '', email: email, username: slug,
      department: dept, program: program || '', accessLevel: access,
      status: status, phone: phone || '', createdAt: created, lastLogin: lastLogin || '',
      accountTier: meta.accountTier || TIER_BY_ROLE[role] || 'Student Tier',
      year: meta.year || '',
      requestedAt: meta.requestedAt || created
    };
  }
  var USERS = [
    u('Josefina', 'Reyes', 'Administrator', '', 'josefina.reyes@uc.edu.ph', 'Information Systems Office', '', 'Administrator — Full System Access', 'Active', '2025-06-02T08:00:00', '2026-09-16T08:12:00', '+63 917 555 0101'),
    u('Ryan', 'Dela Cruz', 'Student', '22-00104', 'ryan.delacruz@uc.edu.ph', 'College of Computer Studies', 'BS Information Technology', 'Student — Portal Access', 'Active', '2025-06-14T09:30:00', '2026-09-16T07:48:00', '+63 918 555 0244'),
    u('Maria', 'Alvarez', 'Faculty', '', 'maria.alvarez@uc.edu.ph', "Dean's Office", 'College of Education', 'Faculty — Faculty Portal Access', 'Active', '2025-06-18T10:15:00', '2026-09-15T16:20:00', '+63 917 555 0318'),
    u('Emmanuel', 'Mercado', 'Student', '23-00872', 'emmanuel.mercado@uc.edu.ph', 'College of Business Administration', 'BS Accountancy', 'Student — Portal Access', 'Active', '2025-07-03T11:05:00', '2026-09-15T16:41:00', '+63 919 555 0477'),
    u('Joseph', 'Gomez', 'Student', '24-01193', 'joseph.gomez@uc.edu.ph', 'College of Engineering', 'BS Civil Engineering', 'Student — Portal Access', 'Active', '2025-07-21T13:40:00', '2026-09-15T08:55:00', '+63 916 555 0529'),
    u('Julie', 'Acabado', 'Student', '24-01208', 'julie.acabado@uc.edu.ph', 'College of Arts & Sciences', 'AB Communication', 'Student — Portal Access', 'Pending', '2026-09-14T14:22:00', '', '+63 917 555 0683'),
    u('Nathaniel', 'Cruz', 'Staff', '', 'nathaniel.cruz@uc.edu.ph', "Registrar's Office", '', 'Staff — Records Management Access', 'Active', '2025-06-20T08:45:00', '2026-09-16T07:30:00', '+63 918 555 0710'),
    u('Andrea', 'Villanueva', 'Staff', '', 'andrea.villanueva@uc.edu.ph', 'Guidance & Testing Center', '', 'Staff — Case Records Access', 'Active', '2025-06-25T09:10:00', '2026-09-15T15:05:00', '+63 917 555 0842'),
    u('Kristine Joy', 'Tan', 'Student', '24-01455', 'kristine.tan@uc.edu.ph', 'College of Nursing', 'BS Nursing', 'Student — Portal Access', 'Active', '2025-08-05T10:00:00', '2026-09-16T06:58:00', '+63 919 555 0931'),
    u('Mark Anthony', 'Lim', 'Student', '26-00017', 'mark.lim@uc.edu.ph', 'College of Computer Studies', 'BS Information Systems', 'Student — Portal Access', 'Pending', '2026-09-15T09:12:00', '', '+63 916 555 1044'),
    u('Pia', 'Santiago', 'Administrator', '', 'pia.santiago@uc.edu.ph', 'Office of Student Affairs & Services', '', 'Administrator — Broadcast Management', 'Active', '2025-06-09T08:20:00', '2026-09-16T08:40:00', '+63 917 555 1155'),
    u('Dennis', 'Bautista', 'Faculty', '', 'dennis.bautista@uc.edu.ph', "Dean's Office", 'College of Engineering', 'Faculty — Faculty Portal Access', 'Inactive', '2025-07-11T13:00:00', '2026-05-28T11:15:00', '+63 918 555 1268'),
    u('Alyssa Marie', 'Uy', 'Student', '23-00741', 'alyssa.uy@uc.edu.ph', 'College of Business Administration', 'BS Management Accounting', 'Student — Portal Access', 'Active', '2025-07-15T15:35:00', '2026-09-14T11:20:00', '+63 917 555 1379'),
    u('Samuel', 'Ortega', 'Administrator', '', 'samuel.ortega@uc.edu.ph', 'Office of the President', '', 'Administrator — Full System Access', 'Active', '2025-06-01T07:00:00', '2026-09-15T14:02:00', '+63 919 555 1480')
  ];

  /* Pending self-registrations awaiting identity verification.
     Timestamps sit just before OSAS.SEED_NOW (2026-09-16T09:45) so the
     "N mins ago" column of the verification queue reads correctly. */
  USERS.push(
    u('Alex', 'Rivera', 'Student', '26-00041', 'alex.rivera@student.uc.edu.ph', 'College of Computer Studies', 'BS Information Technology', 'Student — Portal Access', 'Pending', '2026-09-16T09:35:00', '', '+63 917 555 2101', { year: 'First Year' }),
    u('Maria', 'Torres', 'Student', '26-00042', 'maria.torres@student.uc.edu.ph', 'College of Business Administration', 'BS Accountancy', 'Student — Portal Access', 'Pending', '2026-09-16T09:21:00', '', '+63 918 555 2146', { year: 'First Year' }),
    u('James', 'Chen', 'Student', '26-00043', 'james.chen@student.uc.edu.ph', 'College of Engineering', 'BS Civil Engineering', 'Student — Portal Access', 'Pending', '2026-09-16T09:10:00', '', '+63 916 555 2183', { year: 'Second Year' }),
    u('Sarah', 'Lee', 'Student', '26-00044', 'sarah.lee@student.uc.edu.ph', 'College of Nursing', 'BS Nursing', 'Student — Portal Access', 'Pending', '2026-09-16T08:57:00', '', '+63 919 555 2207', { year: 'First Year' }),
    u('David', 'Kim', 'Student', '26-00045', 'david.kim@student.uc.edu.ph', 'College of Arts & Sciences', 'AB Communication', 'Student — Portal Access', 'Pending', '2026-09-16T08:44:00', '', '+63 917 555 2251', { year: 'Third Year' })
  );

  /* ---------- Dashboard: live system audit log ---------- */
  function log(tone, icon, title, text, at, ago) {
    return { id: 'LOG-' + at, tone: tone, icon: icon, title: title, text: text, at: at, ago: ago };
  }
  var AUDIT = [
    log('green', 'check', 'New Ticket Resolved #TCS-2026-03',
      'Emmanuel Mercado · Orientation Content Feedback was marked as resolved by OSAS Support.', '2026-09-16T09:41:00', '41 mins ago'),
    log('brand', 'megaphone', 'Announcement Broadcast',
      'OSAS Admin published "Scholarship & Financial Assistance Application for AY 2026-2027" to all student portals.', '2026-09-16T08:52:00', '1 hr ago'),
    log('amber', 'alert', 'High-Support Volume Alert',
      'Support ticket volume increased by +12% compared to last week. 5 tickets are still awaiting action.', '2026-09-16T07:44:00', '2 hrs ago'),
    log('blue', 'target', 'Batch Completion Milestone',
      '89.2% of the incoming freshmen orientation batches reached the 90% module completion threshold.', '2026-09-16T02:30:00', '7 hrs ago'),
    log('brand', 'users', 'Account Activated',
      'Mark Anthony Lim activated a new student portal account (26-00017).', '2026-09-15T18:12:00', '15 hrs ago'),
    log('amber', 'alert', 'Pending Regulation Review',
      '2 policies in Rules & Regulations are still tagged as PENDING REVIEW by the legal office.', '2026-09-15T13:05:00', '20 hrs ago'),
    log('blue', 'broadcast', 'Scheduled Broadcast Queued',
      'NOTIF-2026-01 "Invitation: Annual Student Organization Recruitment for 2026" is queued for Sep 22, 2026.', '2026-09-15T10:00:00', '23 hrs ago'),
    log('green', 'shield', 'Security Policy Sync',
      'Portal password policy updated: minimum 10 characters with quarterly rotation enforced.', '2026-09-14T16:45:00', '2 days ago')
  ];
    /* ---------- Module 2: Announcement & Information Broadcast Management ---------- */
  function pad3(n) { return String(n).padStart(3, '0'); }
  function docBody(summary) {
    return '<p>' + summary + '</p>' +
      '<p>Please be guided accordingly. All concerned students are required to read the full memorandum posted in the Student Portal under Announcements.</p>' +
      '<h4>Key reminders</h4><ul>' +
      '<li>Keep your portal account and contact details updated at all times.</li>' +
      '<li>Coordinate with your respective college secretary for specific concerns.</li>' +
      '<li>Official announcements are released only through this portal and the university email.</li>' +
      '</ul><p>For questions and clarifications, please contact the Office of Student Affairs and Services.</p>';
  }
  /* an(title, category, audience, status, date, views, summary) */
  function an(n, title, category, audience, status, date, views, summary) {
    var summaryText = summary || ('Automated release queued to ' + audience + '. Review the full memorandum before the scheduled broadcast.');
    return {
      id: 'ANN-2026-' + pad3(n), code: 'ANN-2026-' + pad3(n),
      title: title, category: category, audience: audience, status: status,
      priority: 'Standard Priority', date: date,
      publishedAt: status === 'Published' ? date : '',
      scheduledFor: status === 'Scheduled' ? date : '',
      updatedAt: date, views: views || 0, author: 'OSAS Administration',
      summary: summaryText, body: docBody(summaryText)
    };
  }
  var ANNOUNCEMENTS = [
    an(1, '1st Semester Enrollment Guidelines for AY 2026-2027', 'Registrar & Enrollment', 'All Student Portals', 'Published', '2025-09-05T09:00:00', 220,
      'Complete enrollment guidelines, documentary requirements, and the official registration flow for the first semester of AY 2026-2027.'),
    an(2, 'Notice of Tuition Fee Adjustment for Academic Year 2026-2027', 'Finance & Accounting', 'All Student Portals', 'Published', '2025-09-01T10:30:00', 186,
      'The approved tuition and miscellaneous fee schedule for AY 2026-2027, including installment options and payment channels.'),
    an(3, 'System Downtime for Scheduled Maintenance of Student Portal', 'System & IT Services', 'All Student Portals', 'Published', '2025-08-28T16:00:00', 148,
      'The Student Portal will be unavailable on Sep 2, 2025 from 10:00 PM to 11:00 PM while the enrollment database is upgraded.'),
    an(4, 'Second Semester Enlistment Schedule for All Colleges', 'Registrar & Enrollment', 'All Student Portals', 'Published', '2025-08-15T08:00:00', 132,
      'College-by-college enlistment windows, subject pre-requisite checks, and the procedure for filing overload requests.'),
    an(5, 'Campus Student Organization Fair and Events for First Semester', 'Student Affairs', 'All Student Portals', 'Published', '2025-08-10T09:15:00', 118,
      'Join the annual student organization fair. Browse accredited organizations and sign up for recruitment activities.'),
    an(6, 'Update on Portal Security Features and Password Policies', 'System & IT Services', 'All Student Portals', 'Published', '2025-08-05T13:20:00', 96,
      'New password complexity rules, quarterly rotation, and two-step verification for administrative accounts.'),
    an(7, 'Health Advisory from the University Medical Services Office', 'Health & Medical Services', 'All Student Portals', 'Published', '2025-08-03T07:45:00', 84,
      'Seasonal health advisory, clinic hours, and the schedule of free medical and dental consultations for students.'),
    an(8, 'Revised Schedule of Final Examinations for the Second Semester', 'Academic Affairs', 'All Student Portals', 'Published', '2025-09-08T11:00:00', 72,
      'The revised final examination matrix per college, including special examination guidelines for students with conflicts.'),
    an(9, 'University Library Extended Service Hours during Finals Week', 'Library Services', 'All Student Portals', 'Published', '2025-09-10T08:40:00', 58,
      'The Main Library and e-Resource Hub will extend service hours to 10:00 PM during the final examination week.'),
    an(10, 'Scholarship & Financial Assistance Application for AY 2026-2027', 'Scholarship & Financial Assistance', 'All Student Portals', 'Published', '2026-09-16T08:52:00', 46,
      'The Scholarship and Financial Assistance Office is now accepting applications for academic and grant-based financial aid.'),
    an(11, 'Guidelines for Incoming Freshmen during Orientation Period', 'Student Affairs', 'Incoming Freshmen', 'Published', '2025-08-20T09:00:00', 42,
      'Attendance requirements, orientation schedules, and the mandatory module completion before the enlistment period.'),
    an(12, 'Call for Student Leader Nominees for the Supreme Student Council', 'Student Affairs', 'All Student Portals', 'Published', '2025-09-14T10:10:00', 38,
      'Nomination requirements, filing deadlines, and eligibility criteria for the Supreme Student Council elections.'),
    an(13, 'Campus Safety Reminders during the Rainy Season', 'Student Affairs', 'All Student Portals', 'Archived', '2025-07-18T09:30:00', 64,
      'Archived advisory on campus safety measures, flooded walkway detours, and emergency hotline numbers.')
  ];
    /* Scheduled and draft broadcasts */
  ANNOUNCEMENTS.push(
    an(14, 'Enrollment Confirmation Deadlines for Incoming Freshmen', 'Registrar & Enrollment', 'Incoming Freshmen', 'Scheduled', '2026-09-22T08:00:00', 0,
      'Freshmen must confirm their official enrollment through the portal on or before the published college deadline.'),
    an(15, 'Student Organization Renewal Requirements for AY 2026-2027', 'Student Affairs', 'Student Officers', 'Scheduled', '2026-09-24T09:00:00', 0,
      'Accredited organizations must submit renewal documents, adviser endorsements, and the updated membership roster.'),
    an(16, 'Midterm Grade Submission Reminder for Faculty Members', 'Academic Affairs', 'Faculty & Staff', 'Scheduled', '2026-09-28T07:30:00', 0,
      'Faculty members are reminded of the midterm grade submission deadline through the faculty portal.'),
    an(17, 'Annual Medical Clearance Verification Reminder', 'Health & Medical Services', 'All Student Portals', 'Scheduled', '2026-10-01T08:00:00', 0,
      'Students must complete the annual medical clearance verification at the University Medical Services Office.'),
    an(18, 'Career & Internship Orientation Schedule for Graduating Students', 'Student Affairs', 'Graduating Students', 'Scheduled', '2026-10-05T09:00:00', 0,
      'The Career and Placement Office releases the internship orientation schedule for graduating students.'),
    an(19, 'Portal Maintenance Advisory: Database Upgrade', 'System & IT Services', 'All Student Portals', 'Scheduled', '2026-10-09T22:00:00', 0,
      'A three-hour maintenance window is scheduled for the portal database upgrade. Save your work in advance.'),
    an(20, 'Scholarship Renewal Requirements for Existing Grantees', 'Scholarship & Financial Assistance', 'All Student Portals', 'Scheduled', '2026-10-12T08:00:00', 0,
      'Existing grantees must submit the renewal requirements and maintain the required general weighted average.'),
    an(21, 'Intramurals 2026 Participation Guidelines', 'Student Affairs', 'All Student Portals', 'Scheduled', '2026-10-16T09:00:00', 0,
      'Team registration, eligibility, and the official schedule of events for Intramurals 2026.'),
    an(22, 'Reminder: Mandatory Orientation Completion Deadline', 'Student Affairs', 'Incoming Freshmen', 'Draft', '2026-09-16T07:05:00', 0,
      'Portal access will be temporarily restricted for incoming freshmen who have not completed the orientation modules within thirty days of enlistment.'),
    an(23, 'Guidelines on Off-Campus Student Activities', 'Student Affairs', 'Student Officers', 'Draft', '2026-09-15T15:20:00', 0,
      'Draft circular covering travel permits, adviser supervision, and safety requirements for off-campus activities.'),
    an(24, 'Proposed Revision of the Student Grievance Procedure', 'Academic Affairs', 'All Student Portals', 'Draft', '2026-09-11T10:35:00', 0,
      'Draft revision of the student grievance procedure submitted for review by the legal and academic affairs offices.')
  );
    /* ---------- Module 3: Student Code of Conduct & Related Regulations ---------- */
  function regBody(title, summary, effective) {
    return '<h4>Article I — Coverage and Scope</h4>' +
      '<p>This policy governs all officially enrolled students of the University of Cebu — Lapu-Lapu and Mandaue campuses, including students on practicum, internship, or any off-campus academic engagement.</p>' +
      '<h4>Article II — Policy Statement</h4><p>' + summary + '</p>' +
      '<h4>Article III — Implementing Guidelines</h4><ul>' +
      '<li>All students are duty-bound to read and observe the provisions of "' + title + '".</li>' +
      '<li>Violations shall be evaluated by the Office of Student Affairs and Services through due process.</li>' +
      '<li>Sanctions shall be proportionate to the offense and consistent with existing university regulations.</li>' +
      '<li>Reports and cases shall be documented and retained in accordance with the data privacy policy.</li>' +
      '</ul>' +
      '<h4>Article IV — Effectivity</h4>' +
      '<p>This policy takes effect on ' + effective + ' and shall remain in force until otherwise revised by the Board of Trustees through a formal policy bulletin.</p>';
  }
  /* r(n, title, category, status, updated, views, effective, review, approval, summary) */
  function r(n, title, category, status, updated, views, effective, review, approval, summary) {
    return {
      id: 'REG-2026-' + pad3(n), code: 'REG-2026-' + pad3(n), referenceCode: 'UC-OSAS-' + pad3(n),
      title: title, category: category, audience: 'All Students', status: status,
      updatedAt: updated, effectiveDate: effective, reviewSchedule: review, approvalBody: approval,
      views: views || 0, summary: summary, body: regBody(title, summary, effective)
    };
  }
  var REGULATIONS = [
    r(1, 'Revised Student Code of Conduct and Disciplinary Procedures', 'Conduct & Discipline', 'Published', '2025-09-05T09:00:00', 420, 'September 15, 2025', 'Annual review every June', 'Board of Trustees — Committee on Student Affairs',
      'This Code defines the standards of student behaviour, the classification of minor and major offenses, and the disciplinary procedures observed by the university.'),
    r(2, 'University Uniform and Identification Card Policy', 'Conduct & Discipline', 'Published', '2025-09-01T08:30:00', 380, 'August 1, 2025', 'Every two years', 'Office of Student Affairs and Services',
      'Prescribes the official uniform, identification card wearing, and the penalties for non-compliance during official school days and activities.'),
    r(3, 'Policy on Student Attendance and Absence Documentation', 'Academic Policies', 'Published', '2025-08-25T10:00:00', 340, 'First semester AY 2025-2026', 'Annual review every June', 'Academic Council',
      'Sets the maximum allowable absences, the documentary requirements for excused absences, and the make-up examination privileges of students.'),
    r(4, 'Guidelines on Student Organizations and Activities', 'Student Organizations', 'Published', '2025-08-20T13:15:00', 310, 'First semester AY 2025-2026', 'Annual review every June', 'Office of Student Affairs and Services',
      'Covers recognition and renewal of student organizations, adviser requirements, activity permits, and financial accountability.'),
    r(5, 'Rules on Use of University Facilities and Equipment', 'Campus Facilities', 'Published', '2025-08-15T09:45:00', 280, 'August 1, 2025', 'Annual review every June', 'Physical Facilities Management Office',
      'Defines the reservation process, permitted use, and the liability of students for damage to university facilities and equipment.'),
    r(6, 'Data Privacy and Confidentiality of Student Records', 'Data Privacy & Records', 'Published', '2025-08-10T11:20:00', 260, 'July 1, 2025', 'Annual review every June', 'Data Privacy Office',
      'Establishes how student records are collected, stored, accessed, and released in compliance with the Data Privacy Act of 2012.')
  ];
    REGULATIONS.push(
    r(7, 'Anti-Bullying and Anti-Harassment Policy', 'Conduct & Discipline', 'Published', '2025-08-05T14:00:00', 240, 'July 15, 2025', 'Annual review every June', 'Committee on Discipline and Student Welfare',
      'Prohibits bullying, harassment, and any form of violence on campus and online, and provides the reporting and intervention mechanism.'),
    r(8, 'Policy on Student Dress Code during Official University Activities', 'Conduct & Discipline', 'Published', '2025-07-30T08:10:00', 220, 'July 1, 2025', 'Annual review every June', 'Office of Student Affairs and Services',
      'Prescribes the proper attire for convocations, retreats, seminars, and other official university activities.'),
    r(9, 'Guidelines on Academic Honors and Latin Honors Eligibility', 'Academic Policies', 'Published', '2025-07-25T09:30:00', 200, 'Second semester AY 2025-2026', 'Annual review every June', 'Academic Council',
      'Specifies the general weighted average, residency, and conduct requirements for academic distinction and Latin honors.'),
    r(10, 'Policy on Laboratory Safety and Proper Use of Equipment', 'Health & Safety', 'Published', '2025-07-20T10:40:00', 180, 'First semester AY 2025-2026', 'Annual review every June', 'Laboratory Safety Committee',
      'Sets the safety protocols, protective equipment requirements, and incident reporting procedure for all laboratory-based courses.'),
    r(11, 'Guidelines on Student Financial Assistance and Refunds', 'Academic Policies', 'Published', '2025-07-15T11:50:00', 160, 'June 1, 2025', 'Annual review every June', 'Finance and Scholarship Committee',
      'Details the scholarship grants, installment arrangements, and the refund computation for officially withdrawn courses.'),
    r(12, 'Policy on Student Grievances and Disciplinary Appeals', 'Conduct & Discipline', 'Published', '2025-07-08T08:00:00', 250, 'June 15, 2025', 'Annual review every June', 'Board of Trustees — Committee on Student Affairs',
      'Provides the procedural steps for filing grievances, the composition of hearing panels, and the appeal process available to students.'),
    r(13, 'Academic Integrity and Anti-Plagiarism Policy', 'Academic Policies', 'Pending Review', '2025-08-28T15:25:00', 0, 'To be determined upon approval', 'Annual review every June', 'Academic Council',
      'Defines plagiarism, unauthorized collaboration, and the sanctions for academic dishonesty across all colleges and programs.'),
    r(14, 'Revised Policy on Absences for Students Representing the University', 'Academic Policies', 'Pending Review', '2026-09-09T09:15:00', 0, 'To be determined upon approval', 'Annual review every June', 'Academic Council',
      'Clarifies the excused absence privileges of students representing the university in official competitions and conferences.'),
    r(15, 'Campus Safety and Emergency Response Policy', 'Health & Safety', 'Pending Review', '2026-09-12T13:45:00', 0, 'To be determined upon approval', 'Annual review every June', 'Campus Safety Committee',
      'Establishes the emergency response protocol, evacuation procedures, and the responsibilities of students during campus emergencies.'),
    r(16, 'Proposed Policy on the Use of AI Tools in Academic Coursework', 'Academic Policies', 'Draft', '2026-09-14T10:05:00', 0, 'To be determined upon approval', 'Annual review every June', 'Academic Council',
      'Draft policy on the acceptable use of generative AI tools in coursework, examinations, and research outputs.'),
    r(17, 'Guidelines on On-Campus Parking for Students', 'Campus Facilities', 'Archived', '2025-05-12T10:30:00', 95, 'May 15, 2025', 'Superseded by the 2025 Traffic Management Bulletin', 'Physical Facilities Management Office',
      'Archived guidelines on student parking zones, stickers, and the traffic flow inside the campus. Replaced by the 2025 Traffic Management Bulletin.')
  );
    /* ---------- Module 4: Student Orientation & Information Module ---------- */
  function modBody(title, description, objectives) {
    return '<p>' + description + '</p>' +
      '<h4>Learning objectives</h4><ul>' + objectives.map(function (o) { return '<li>' + o + '</li>'; }).join('') + '</ul>' +
      '<h4>How to complete this module</h4>' +
      '<p>Read all sections, watch the embedded campus briefing, then answer the ten-item knowledge check at the end of the module. A module is marked complete once the knowledge check is passed with at least 8 of 10 correct answers.</p>' +
      '<p>Estimated reading time: 12 to 15 minutes. You may pause and resume anytime — your progress is saved automatically in the portal.</p>';
  }
  /* m(n, title, category, status, updated, learners, rate, duration, version, mandatory, description) */
  function m(n, title, category, status, updated, learners, rate, duration, version, mandatory, description) {
    var objectives = [
      'Understand the purpose of the ' + title.replace('Orientation Module — ', '') + ' module.',
      'Identify the offices and personnel that can assist students.',
      'Apply the discussed guidelines in daily campus life.'
    ];
    return {
      id: 'ORIENT-' + pad3(n), code: 'ORIENT-' + pad3(n), title: title, category: category,
      status: status, audience: 'Incoming Freshmen', updatedAt: updated,
      version: version, duration: duration, mandatory: mandatory, learners: learners,
      completions: Math.round(learners * rate / 100), completionRate: rate,
      attachments: mandatory ? 2 : 1,
      description: description, objectives: objectives,
      body: modBody(title, description, objectives)
    };
  }
  var MODULES = [
    m(1, 'Orientation Module — Overview: Welcome to UC-LM', 'Orientation Module — Overview', 'Published', '2026-08-29T08:00:00', 1240, 92, 14, 'v3.2', true,
      'Welcome message from the Vice President for Student Affairs, the university profile, and the orientation requirements for incoming freshmen.'),
    m(2, 'Orientation Module — Student Code of Conduct', 'Orientation Module — Student Code of Conduct', 'Published', '2026-08-29T08:30:00', 1186, 91, 18, 'v2.6', true,
      'Walkthrough of the Student Code of Conduct, the classification of offenses, and the disciplinary procedure in plain language.'),
    m(3, 'Orientation Module — Campus Facilities & Services', 'Orientation Module — Campus Facilities & Services', 'Published', '2026-08-30T09:15:00', 1104, 90, 15, 'v2.1', true,
      'Guided tour of campus buildings, the library, laboratories, the clinic, the canteen, and the student service counters.'),
    m(4, 'Orientation Module — Academic Policies & Procedures', 'Orientation Module — Academic Policies & Procedures', 'Published', '2026-08-31T10:00:00', 1068, 89, 20, 'v3.0', true,
      'Academic load limits, grading system, add-and-drop rules, attendance policies, and graduation requirements.'),
    m(5, 'Orientation Module — Student Support Services', 'Orientation Module — Student Support Services', 'Published', '2026-09-02T08:45:00', 998, 88, 16, 'v1.9', true,
      'Guidance and counseling services, scholarship assistance, health services, and student welfare programs.'),
    m(6, 'Orientation Module — Co-Curricular Programs', 'Orientation Module — Co-Curricular Programs', 'Published', '2026-09-04T09:30:00', 942, 86, 12, 'v1.4', false,
      'Student organizations, leadership programs, sports, community extension, and how to join accredited activities.'),
    m(7, 'Orientation Module — Digital Portal Guide', 'Orientation Module — Digital Portal Guide', 'Published', '2026-09-06T11:20:00', 1130, 90, 13, 'v4.1', true,
      'Portal navigation, announcement tracking, account security, and the procedure for filing support tickets online.'),
    m(8, 'Orientation Module — Health & Wellness', 'Orientation Module — Health & Wellness', 'Published', '2026-09-08T13:00:00', 886, 86, 14, 'v1.2', false,
      'Campus health protocols, mental wellness support, emergency response, and the annual medical clearance requirement.'),
    m(9, 'Orientation Module — Financial Literacy for Freshmen', 'Orientation Module — Academic Policies & Procedures', 'Draft', '2026-09-15T15:40:00', 0, 0, 11, 'v0.4', false,
      'Draft module covering tuition payment schedules, budgeting for student allowances, and financial assistance options.'),
    m(10, 'Orientation Module — 2025 Campus Tour (Legacy)', 'Orientation Module — Campus Facilities & Services', 'Archived', '2025-07-01T09:00:00', 412, 81, 16, 'v1.0', false,
      'Archived 2025 campus tour module retained for reference. Superseded by the Campus Facilities & Services module.')
  ];
    /* ---------- Module 5: Events & Activities Management ---------- */
  /* ev(n, title, category, status, start, end, venue, mode, audience, capacity, registered, description) */
  function ev(n, title, category, status, start, end, venue, mode, audience, capacity, registered, description) {
    return {
      id: 'EVT-2026-' + pad3(n), code: 'EVT-2026-' + pad3(n), title: title, category: category,
      status: status, startAt: start, endAt: end, venue: venue, mode: mode, audience: audience,
      capacity: capacity, registered: registered, description: description,
      organizer: 'Office of Student Affairs and Services',
      verification: 'Approved by the Office of Student Affairs and Services'
    };
  }
  var EVENTS = [
    ev(12, 'Freshmen Orientation Assembly — Day 1', 'Orientation & Onboarding', 'Scheduled', '2026-09-22T08:00:00', '2026-09-22T12:00:00', 'UC-LM University Gymnasium', 'On-campus', 'Incoming Freshmen', 1500, 1284,
      'Official welcome assembly for incoming freshmen including the campus orientation briefing and module walkthrough.'),
    ev(13, 'Student Organization Recruitment Fair', 'Student Organizations', 'Scheduled', '2026-09-25T09:00:00', '2026-09-27T17:00:00', 'Campus Grounds & Activity Center', 'On-campus', 'All Students', 3000, 2140,
      'Three-day recruitment fair where accredited student organizations present their programs and accept new members.'),
    ev(14, 'Mental Health Awareness Seminar', 'Guidance & Counseling', 'Scheduled', '2026-10-02T13:00:00', '2026-10-02T16:30:00', 'Audio-Visual Room, Building B', 'Hybrid', 'All Students', 320, 268,
      'Talks on stress management, help-seeking behaviour, and the support services available at the Guidance and Testing Center.'),
    ev(15, 'Intramurals 2026 Opening Ceremony', 'Sports & Athletics', 'Scheduled', '2026-10-12T07:30:00', '2026-10-12T17:00:00', 'University Oval', 'On-campus', 'All Students', 4000, 3360,
      'Parade of colleges, torch lighting, and the official opening of the Intramurals 2026 sports season.'),
    ev(16, 'Career & Internship Orientation', 'Career & Placement', 'Scheduled', '2026-10-20T09:00:00', '2026-10-20T15:00:00', 'University Function Hall', 'Hybrid', 'Graduating Students', 600, 512,
      'Internship placement requirements, partner company orientations, and resume preparation workshops.'),
    ev(17, 'Leadership Training for Student Officers', 'Leadership Development', 'Scheduled', '2026-11-05T08:00:00', '2026-11-06T17:00:00', 'UC-LM Retreat House', 'Off-campus', 'Student Officers', 180, 164,
      'Two-day leadership formation for newly elected officers covering governance, ethics, and activity planning.'),
    ev(18, 'Anti-Bullying Awareness Campaign', 'Guidance & Counseling', 'Completed', '2026-09-10T08:00:00', '2026-09-10T16:00:00', 'Campus Grounds', 'On-campus', 'All Students', 2500, 2418,
      'Campus-wide campaign on bullying prevention, reporting channels, and the support services for affected students.'),
    ev(19, 'Campus Clean-Up and Tree Planting Drive', 'Community Extension', 'Completed', '2026-09-05T06:30:00', '2026-09-05T11:00:00', 'UC-LM Grounds & Coastal Area', 'Off-campus', 'All Students', 800, 736,
      'Community extension activity for the National Clean-Up Month including tree planting and coastal clean-up.')
  ];
    /* ---------- Module 7: Feedback & Support Center ---------- */
  function plusHours(iso, hours) {
    var d = new Date(iso);
    d.setHours(d.getHours() + hours);
    function p(v) { return String(v).padStart(2, '0'); }
    return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + 'T' +
      p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
  }
  /* t(n, student, email, studentId, program, category, subject, description, priority, status, submitted, assigned) */
  function t(n, student, email, studentId, program, category, subject, description, priority, status, submitted, assigned) {
    var messages = [{
      author: student, role: 'Student', at: submitted,
      body: description + ' I have already checked my portal account and the concern is still unresolved. Thank you for your assistance.'
    }];
    if (status !== 'Open') {
      messages.push({
        author: assigned, role: 'OSAS Support', at: plusHours(submitted, 2),
        body: 'Good day! We have received your concern and forwarded it to the concerned office. We will update this ticket as soon as the corrective action is completed.'
      });
    }
    if (status === 'Resolved') {
      messages.push({
        author: assigned, role: 'OSAS Support', at: plusHours(submitted, 26),
        body: 'Your concern has been addressed and verified. We are closing this ticket as resolved — please reopen it if the issue recurs.'
      });
    }
    return {
      id: 'TCS-2026-' + pad3(n), code: 'TCS-2026-' + pad3(n), student: student, studentEmail: email,
      studentId: studentId, program: program, category: category, subject: subject, description: description,
      priority: priority, status: status, submittedAt: submitted, updatedAt: plusHours(submitted, 2),
      assignedTo: assigned, messages: messages
    };
  }
  var TICKETS = [
    t(6, 'Mark Anthony Lim', 'mark.lim@uc.edu.ph', '26-00017', 'BS Information Systems', 'Account & Portal Access',
      'Cannot activate new portal account',
      'The activation link sent to my university email keeps returning an expired token message even though I clicked it within a few minutes.',
      'Medium', 'Open', '2026-09-16T07:42:00', 'Andrea Villanueva'),
    t(5, 'Joseph D. Gomez', 'joseph.gomez@uc.edu.ph', '24-01193', 'BS Civil Engineering', 'LMS Access Issues',
      'Unable to access learning portal',
      'I cannot open the learning management system since Sep 15. The portal returns a session expired error whenever I sign in from my phone.',
      'High', 'Open', '2026-09-15T08:55:00', 'Nathaniel Cruz'),
    t(4, 'Julie Acabado', 'julie.acabado@uc.edu.ph', '24-01208', 'AB Communication', 'Student ID Verification',
      'Request for official student ID processing correction',
      'My surname was misspelled in the student ID encoding. I would like to request a correction and reprint of the official identification card.',
      'Low', 'In Progress', '2026-09-15T10:55:00', 'Andrea Villanueva'),
    t(3, 'Emmanuel Mercado', 'emmanuel.mercado@uc.edu.ph', '23-00872', 'BS Accountancy', 'Orientation Content Feedback',
      'Comments on Orientation module content',
      'The Campus Facilities module lists an outdated location for the accounting laboratory. Kindly update the module content.',
      'Low', 'Resolved', '2026-09-15T16:41:00', 'Pia Santiago'),
    t(2, 'Alyssa Marie Uy', 'alyssa.uy@uc.edu.ph', '23-00741', 'BS Management Accounting', 'Account & Portal Access',
      'Password reset link expired twice',
      'The password reset link sent through email expired before I could use it. I have requested it twice already.',
      'Medium', 'Open', '2026-09-14T11:20:00', 'Josefina Reyes'),
    t(1, 'Kristine Joy Tan', 'kristine.tan@uc.edu.ph', '24-01455', 'BS Nursing', 'Enrollment & Records',
      'Enlisted subject does not appear in my schedule',
      'NCM 101 appears as enlisted but it is missing from my official schedule of classes in the portal.',
      'High', 'In Progress', '2026-09-12T09:05:00', 'Nathaniel Cruz')
  ];
    /* ---------- Module 8: Automated Broadcasts & Student Alerts ---------- */
  /* b(n, subject, message, channel, status, at, audience, reach) */
  function b(n, subject, message, channel, status, at, audience, reach) {
    return {
      id: 'NOTIF-2026-' + pad3(n), code: 'NOTIF-2026-' + pad3(n), subject: subject, message: message,
      channel: channel, status: status, at: at, audience: audience, reach: reach || 0,
      delivered: status === 'Sent' ? reach : 0,
      opened: status === 'Sent' ? Math.round(reach * 0.68) : 0
    };
  }
  var BROADCASTS = [
    b(1, 'Invitation: Annual Student Organization Recruitment for 2026',
      'All accredited student organizations will hold recruitment activities from September 25 to 27. Visit the Activity Center to sign up.',
      'Portal + Email', 'Scheduled', '2026-09-22T06:25:00', 'All Student Portals', 0),
    b(2, 'System Maintenance: Student Portal Temporary Outage',
      'The Student Portal will be temporarily unavailable on September 10 from 10:00 PM to 11:00 PM for a scheduled database upgrade.',
      'Portal Notification', 'Sent', '2026-09-10T15:00:00', 'All Student Portals', 13980),
    b(3, 'Health Advisory: Annual Medical Clearance Verification',
      'Please submit your physical examination results to the University Health Clinic prior to enrollment. Walk-in schedules are open daily.',
      'University Email', 'Pending', '2026-09-12T10:00:00', 'All Student Portals', 0),
    b(4, 'URGENT: Campus Weather Advisory & Class Suspension',
      'All classes in both the Lapu-Lapu and Mandaue campuses are suspended starting 3:30 PM today due to severe weather conditions.',
      'SMS Alert', 'Sent', '2026-09-15T15:30:00', 'All Student Portals', 13980),
    b(5, 'Reminder: Mandatory Orientation Completion Deadline',
      'Portal access will be temporarily restricted for incoming freshmen who have not completed the orientation modules within thirty days of enlistment.',
      'Portal + Email', 'Draft', '2026-09-16T07:05:00', 'Incoming Freshmen', 0),
    b(6, 'Scholarship & Financial Assistance Application Open',
      'OSAS is now accepting scholarship grants for Academic Year 2026-2027. Submit your requirements on or before September 30.',
      'Portal + Email', 'Sent', '2026-09-01T08:30:00', 'All Student Portals', 13742)
  ];

  /* ---------- Module 10: Reports ---------- */
  /* rep(n, title, category, period, status, format, records, date) */
  function rep(n, title, category, period, status, format, records, date) {
    return {
      id: 'REP-2026-' + pad3(n), code: 'REP-2026-' + pad3(n), title: title, category: category,
      period: period, status: status, format: format, records: records, generatedAt: date,
      generatedBy: 'Josefina Reyes', scope: 'Lapu-Lapu & Mandaue campuses'
    };
  }
  var REPORTS = [
    rep(1, 'User Account & Access Management Report', 'User Management', 'September 2026', 'Completed', 'PDF', 14280, '2026-09-16T08:15:00'),
    rep(2, 'Announcement & Information Broadcast Report', 'Announcements', 'September 2026', 'Completed', 'PDF', 24, '2026-09-16T08:05:00'),
    rep(3, 'Student Orientation & Information Module Report', 'Orientation Content', 'September 2026', 'Completed', 'Excel (XLSX)', 10, '2026-09-15T16:40:00'),
    rep(4, 'Feedback & Support Center Report', 'Feedback & Support', 'September 2026', 'Completed', 'PDF', 6, '2026-09-15T15:10:00'),
    rep(5, 'Viewing & Tracking Analytics Report', 'Viewing & Tracking', 'Third Quarter 2026', 'Completed', 'Excel (XLSX)', 8, '2026-09-15T14:25:00'),
    rep(6, 'Student Code of Conduct & Related Regulations Report', 'Rules & Regulations', 'September 2026', 'Completed', 'PDF', 17, '2026-09-14T11:35:00'),
    rep(7, 'Events & Activities Participation Report', 'Events & Activities', 'September 2026', 'Completed', 'CSV', 8, '2026-09-14T10:05:00'),
    rep(8, 'Portal Traffic & Engagement Summary', 'Viewing & Tracking', 'August 2026', 'Completed', 'PDF', 31, '2026-09-01T09:00:00'),
    rep(9, 'Pending Inquiry Response Time Analysis', 'Feedback & Support', 'Third Quarter 2026', 'Processing', 'Excel (XLSX)', 0, '2026-09-16T09:30:00'),
    rep(10, 'Annual Student Conduct Case Digest', 'Rules & Regulations', 'AY 2026-2027', 'Draft', 'PDF', 0, '2026-09-13T13:50:00')
  ];
  /* ---------- Module 6: Viewing & Tracking (content engagement telemetry) ---------- */
  /* tr(title, type, views, unique, avgTime, engagement, lastViewed, trend, sourceFile) */
  function tr(title, type, views, unique, avgTime, engagement, lastViewed, trend, sourceId) {
    return {
      id: 'TRK-' + sourceId, title: title, type: type, views: views, uniqueVisitors: unique,
      avgTime: avgTime, engagement: engagement, lastViewed: lastViewed, trend: trend, sourceId: sourceId
    };
  }
  var TRACKING = [
    tr('Orientation Module — Overview: Welcome to UC-LM', 'Orientation Module', 12840, 9412, '6m 12s', 92, '2026-09-16T09:20:00', 8.4, 'ORIENT-101'),
    tr('Revised Student Code of Conduct and Disciplinary Procedures', 'Regulation', 9120, 7208, '4m 48s', 78, '2026-09-16T08:35:00', 5.1, 'REG-2026-001'),
    tr('Orientation Module — Digital Portal Guide', 'Orientation Module', 11320, 8520, '5m 40s', 90, '2026-09-16T08:05:00', 6.2, 'ORIENT-107'),
    tr('1st Semester Enrollment Guidelines for AY 2026-2027', 'Announcement', 8640, 6910, '3m 26s', 71, '2026-09-15T17:10:00', 12.6, 'ANN-2026-001'),
    tr('Orientation Module — Campus Facilities & Services', 'Orientation Module', 7930, 5884, '5m 02s', 83, '2026-09-16T07:50:00', 2.7, 'ORIENT-103'),
    tr('Notice of Tuition Fee Adjustment for Academic Year 2026-2027', 'Announcement', 6410, 5102, '2m 58s', 64, '2026-09-14T16:25:00', -1.8, 'ANN-2026-002'),
    tr('University Uniform and Identification Card Policy', 'Regulation', 5220, 3984, '3m 11s', 58, '2026-09-13T15:40:00', 1.2, 'REG-2026-002'),
    tr('Health Advisory from the University Medical Services Office', 'Announcement', 4380, 3215, '2m 12s', 46, '2026-09-12T14:05:00', -4.3, 'ANN-2026-007'),
    tr('Anti-Bullying and Anti-Harassment Policy', 'Regulation', 3150, 2410, '3m 45s', 61, '2026-09-11T11:30:00', 0.9, 'REG-2026-007'),
    tr('Second Semester Enlistment Schedule for All Colleges', 'Announcement', 2980, 2264, '1m 48s', 39, '2026-09-10T09:15:00', -6.7, 'ANN-2026-004')
  ];

  /* ---------- Localization / phase status cards (module 6 summary) ---------- */
  var ENGAGEMENT_NOTES = [
    { id: 'note-1', tone: 'danger', title: 'Low engagement detected', text: 'Four published items are below the 70% engagement threshold. Consider re-broadcasting or revising the content structure.' },
    { id: 'note-2', tone: 'info', title: 'Orientation modules lead engagement', text: 'Orientation content accounts for 46% of all portal views this period, driven by the mandatory freshmen modules.' },
    { id: 'note-3', tone: 'success', title: 'Tracking collector healthy', text: 'Page-view telemetry is streaming normally across all 10 modules. Last aggregation completed at 09:30 AM.' }
  ];

  /* ---------- Public payload ---------- */
  window.OSAS_SEED.users = USERS;
  window.OSAS_SEED.announcements = ANNOUNCEMENTS;
  window.OSAS_SEED.regulations = REGULATIONS;
  window.OSAS_SEED.modules = MODULES;
  window.OSAS_SEED.events = EVENTS;
  window.OSAS_SEED.tickets = TICKETS;
  window.OSAS_SEED.broadcasts = BROADCASTS;
  window.OSAS_SEED.reports = REPORTS;
  window.OSAS_SEED.tracking = TRACKING;
  window.OSAS_SEED.auditLog = AUDIT;
  window.OSAS_SEED.engagementNotes = ENGAGEMENT_NOTES;
  window.OSAS_SEED._partial = false;
})(window);
