# ÅbenForms Video Script Index

Complete video script package for producing professional demo videos.

---

## Quick Start

1. **Read First**: [README.md](README.md) - Complete production guide
2. **Before Recording**: [RECORDING_CHECKLIST.md](RECORDING_CHECKLIST.md) - Pre-flight checklist
3. **Choose Script**: See video scripts below
4. **Record**: Follow script exactly (narration + screen actions + timing)
5. **Edit**: Use post-production notes in each script
6. **Publish**: Follow publishing workflow in README.md

---

## Video Scripts

### 1. Platform Overview (3 minutes)
**File**: [01-PLATFORM_OVERVIEW.md](01-PLATFORM_OVERVIEW.md)

**Purpose**: Introduction to ÅbenForms, value proposition
**Audience**: Municipal decision-makers, IT managers
**Key Messages**:
- Open source alternative to proprietary alternatives
- No per-form licence fees
- No vendor lock-in
- Community-driven

**Script Includes**:
- Exact narration (Danish + English)
- Screen actions with timestamps
- Visual callouts and graphics
- Cost comparison framing (no fabricated figures)
- Feature overview
- GitHub repository showcase
- Call to action

**Complexity**: Simple (mostly slides and homepage)
**Recording Time**: ~1 hour
**Editing Time**: ~4 hours

---

### 2. Parking Permit Demo (5 minutes)
**File**: [02-PARKING_PERMIT_DEMO.md](02-PARKING_PERMIT_DEMO.md)

**Purpose**: End-to-end workflow demonstration (simple example)
**Audience**: Municipal staff, citizens, general public
**Key Messages**:
- A short, guided application flow
- MitID authentication (against a Keycloak mock IdP)
- Payment step (demo mock - not a live payment gateway)
- PDF generation
- SMS confirmation (demo mock)

**Script Includes**:
- Complete workflow walkthrough
- MitID login sequence (mock IdP)
- Form completion (vehicle info, address)
- Payment step (demo mock)
- PDF license generation
- SMS notification (demo mock)

**Test Data Required**:
- Citizen: Jens Jensen (fictional, CPR: 0101701234)
- Vehicle: AB12345 (VW Golf, fictional)
- Payment: Test card 4111 1111 1111 1111
- Zone: Zone 2 - Nørregade området (fictional scenario)

**Complexity**: Medium (demo flow with mocked integrations)
**Recording Time**: ~2 hours
**Editing Time**: ~5 hours

---

### 3. Marriage Booking Demo (5 minutes)
**File**: [03-MARRIAGE_BOOKING_DEMO.md](03-MARRIAGE_BOOKING_DEMO.md)

**Purpose**: Complex dual-authentication workflow
**Audience**: Municipal registry office, engaged couples
**Key Messages**:
- Dual MitID authentication (both partners, against the mock IdP)
- Calendar/booking step (demo mock)
- Reminders (email + SMS, demo mocks)
- Multi-party workflow orchestration
- Celebratory, warm tone

**Script Includes**:
- Dual partner authentication flow
- Calendar and venue selection (demo mock)
- Witness information
- Payment and confirmation (payment is a demo mock)
- Reminder system (demo mock)
- Calendar invitation (.ics) download

**Test Data Required**:
- Partner A: Anna Andersen (fictional, CPR: 0101851234)
- Partner B: Bo Bertelsen (fictional, CPR: 0202881234)
- Witnesses: Carla Christensen, David Davidsen (fictional)
- Venue: Rådhuset, Gammel Festsal (fictional scenario)
- Date: 3 weeks from recording date

**Complexity**: Complex (dual auth, calendar/payroll/calendar steps are demo mocks)
**Recording Time**: ~2.5 hours
**Editing Time**: ~6 hours

---

### 4. Building Permit Demo (7 minutes)
**File**: [04-BUILDING_PERMIT_DEMO.md](04-BUILDING_PERMIT_DEMO.md)

**Purpose**: Advanced features showcase (GIS, SBSYS, GDPR)
**Audience**: Municipal planners, building inspectors, architects
**Key Messages**:
- GIS zoning validation (demo mock)
- Automatic neighbor notification (demo mock)
- Multi-stage approval workflow (ECA flows)
- SBSYS integration (planned, shown as demo mock)
- Field-level CPR encryption and audit logging (real, built into aabenforms_core)
- Faster than a manual paper process

**Script Includes**:
- Project type selection
- MitID + property identification (mock IdP)
- GIS map visualization (demo mock)
- Automatic zoning validation (demo mock)
- Document upload with validation
- Automatic neighbor notification (demo mock)
- Workflow stages (ECA flows)
- SBSYS integration (planned, shown as demo mock)
- GDPR / audit-log view
- Building permit issuance

**Test Data Required**:
- Applicant: Mette Mortensen (fictional, CPR: 0101701234)
- Property: Skovvej 15 (fictional, ID: 751-12345)
- GIS data: Zone Residential, 3 neighbors (mock scenario)
- Documents: 6 PDFs (plans, drawings, calculations)
- Contractor: Byg & Anlæg ApS (fictional, CVR: 98765432)

**Complexity**: Very Complex (GIS/SBSYS shown as demo mocks, multi-stage ECA workflow)
**Recording Time**: ~3 hours
**Editing Time**: ~8 hours

**NOTE**: Several features in this video (GIS, neighbor notification, SBSYS) are demo mocks, not live production integrations. Narrate them as illustrative.

---

### 5. Visual Workflow Builder Tutorial (10 minutes)
**File**: [05-VISUAL_BUILDER_TUTORIAL.md](05-VISUAL_BUILDER_TUTORIAL.md)

**Purpose**: Demonstrate workflow creation without coding
**Audience**: Municipal IT staff, workflow designers, administrators
**Key Messages**:
- No coding required
- Workflow Modeler (visual workflow editor)
- 13 ready-made workflow templates / 18 ECA flows deployed
- Drag-and-drop interface
- Validation and testing
- Template gallery
- Community sharing

**Script Includes**:
- Workflow Modeler editor overview
- Building a simple workflow from scratch (dog registration)
- Adding conditional logic
- Danish municipal task palette
- GIS Validation task (demo mock)
- Neighbor Notification task (demo mock)
- Validation and simulation
- Template gallery browsing
- Export/import workflows
- Advanced features (subprocess, timers, parallel branches)

**Test Data Required**:
- Admin account: admin@demo.aabenforms.dk / demo123
- 13 ready-made workflow templates in gallery
- Workflow Modeler configured with Danish palette

**Complexity**: Complex (technical tutorial, requires clear instruction)
**Recording Time**: ~4 hours
**Editing Time**: ~8 hours

---

## Total Series Statistics

| Video | Duration | Recording | Editing | Total Effort |
|-------|----------|-----------|---------|--------------|
| 01 - Platform Overview | 3 min | 1h | 4h | 5h |
| 02 - Parking Permit | 5 min | 2h | 5h | 7h |
| 03 - Marriage Booking | 5 min | 2.5h | 6h | 8.5h |
| 04 - Building Permit | 7 min | 3h | 8h | 11h |
| 05 - Visual Builder | 10 min | 4h | 8h | 12h |
| **TOTAL** | **30 min** | **12.5h** | **31h** | **43.5h** |

**Production Timeline**:
- Sequential (one at a time): ~10 weeks
- Parallel (overlap phases): ~7 weeks
- Staggered release: ~10 weeks with continuous production

---

## Key Points to Emphasize

These points differentiate ÅbenForms from proprietary alternatives:

### 1. GIS Zoning Validation (Video 04)
- **What**: Automatic property identification and zoning validation
- **Status**: Demo mock - illustrates the intended capability, not a live integration
- **Screen Time**: 90 seconds in Video 04 (1:45-3:00)

### 2. Automatic Neighbor Notification (Video 04)
- **What**: GIS-based neighbor identification and generated letters
- **Status**: Demo mock - illustrates the intended capability
- **Screen Time**: 45 seconds in Video 04 (4:00-4:45)

### 3. Visual Workflow Editor (Video 05)
- **What**: Drag-and-drop workflow creation without coding via the Workflow Modeler
- **Why**: Proprietary alternatives typically have limited workflow customization
- **Impact**: Non-technical staff can create new services
- **Screen Time**: Entire 10-minute video

### 4. Open Source & No Vendor Lock-in (Video 01)
- **What**: GPL-2.0-or-later licensed, self-hostable, community-driven
- **Why**: Proprietary alternatives carry licence and per-integration fees and vendor lock-in
- **Impact**: No per-form licence fees, full data control
- **Screen Time**: 45 seconds in Video 01 (2:15-2:45)

### 5. Danish Municipal Task Palette (Video 05)
- **What**: Pre-configured task types for Danish workflows
- **Why**: Generic BPM tools require extensive configuration
- **Screen Time**: 90 seconds in Video 05 (5:00-6:30)

---

## Supporting Materials

### Production Resources
- [README.md](README.md) - Complete production guide
  - Equipment recommendations ($100-$1,000 budget)
  - Software recommendations (OBS, DaVinci Resolve, etc.)
  - Recording workflow (pre-production, recording, post-production)
  - Export settings (YouTube, web, social media)
  - Background music sources
  - Branding guidelines (colors, fonts, logos)
  - Subtitle creation
  - Publishing workflow
  - Budget estimates
  - Timeline estimates

### Pre-Flight Checklist
- [RECORDING_CHECKLIST.md](RECORDING_CHECKLIST.md) - Complete before every recording
  - Environment preparation (quiet room, no notifications)
  - Computer setup (disable updates, clean desktop)
  - Audio setup (microphone, levels, room tone test)
  - Recording software configuration (OBS settings)
  - Browser setup (clean cache, incognito mode)
  - Demo environment verification (test accounts, data loaded)
  - Video-specific checklists (test data, assets)
  - Script preparation (practice, timing)
  - Emergency troubleshooting

---

## Quick Reference

### Essential Links
- Demo Environment: https://demo.aabenforms.dk
- GitHub Repository: https://github.com/aabenforms/platform
- Documentation: https://docs.aabenforms.dk
- Community: https://community.aabenforms.dk

### Test Accounts (all fictional)
- **Citizen**: 0101701234 (Jens Jensen)
- **Partner A**: 0101851234 (Anna Andersen)
- **Partner B**: 0202881234 (Bo Bertelsen)
- **Admin**: admin@demo.aabenforms.dk / demo123
- **Payment Test Card**: 4111 1111 1111 1111, 12/25, 123

### File Naming Convention
```
[Video#]_[Title]_[Language]_[Resolution]_[Version].mp4

Examples:
01_Platform_Overview_DA_1080p_v1.mp4
02_Parking_Permit_EN_1080p_v1.mp4
04_Building_Permit_DA_720p_web_v2.mp4
```

### Export Settings (Quick Reference)
**YouTube**: 1920x1080, 30fps, H.264, 12-15 Mbps, AAC 192kbps
**Web**: 1280x720, 30fps, H.264, 5-8 Mbps, AAC 192kbps
**Social (Vertical)**: 1080x1920, 30fps, H.264, 8 Mbps

### Branding Colors
- Danish Blue: #0051A5 (primary)
- Danish Red: #C60C30 (accent)
- Yellow: #FFB612 (highlights)
- Green: #2ECC71 (success)

---

## Production Status Tracker

| Video | Script | Recording | Editing | QA | Published |
|-------|--------|-----------|---------|-----|-----------|
| 01 - Platform Overview | Done | Not started | Not started | Not started | Not started |
| 02 - Parking Permit | Done | Not started | Not started | Not started | Not started |
| 03 - Marriage Booking | Done | Not started | Not started | Not started | Not started |
| 04 - Building Permit | Done | Not started | Not started | Not started | Not started |
| 05 - Visual Builder | Done | Not started | Not started | Not started | Not started |

**Legend**: Done | In Progress | Not Started

---

## Recommended Production Order

### Option 1: By Difficulty (Easiest to Hardest)
1. **Video 01** - Platform Overview (slides, no complex demo)
2. **Video 02** - Parking Permit (simple workflow)
3. **Video 03** - Marriage Booking (medium complexity)
4. **Video 05** - Visual Builder (technical but no live integrations)
5. **Video 04** - Building Permit (most complex, save for when experienced)

### Option 2: By Impact (Highest Value First)
1. **Video 04** - Building Permit (key differentiator with GIS)
2. **Video 05** - Visual Builder (empowers municipalities)
3. **Video 01** - Platform Overview (sets context)
4. **Video 02** - Parking Permit (simple example)
5. **Video 03** - Marriage Booking (nice-to-have)

### Option 3: By Release Strategy (Logical Flow)
1. **Video 01** - Platform Overview (introduction)
2. **Video 02** - Parking Permit (simple example to start)
3. **Video 03** - Marriage Booking (show complexity increasing)
4. **Video 04** - Building Permit (wow factor, advanced features)
5. **Video 05** - Visual Builder (end with empowerment message)

**Recommended**: Option 3 (Logical Flow) - best for viewers watching the series

---

## Success Metrics

Set concrete view/retention/engagement targets once the channel has a baseline.
Avoid publishing fabricated benchmark figures before any video exists.

---

## Contact & Support

**Questions about video production?**
- Email: video@aabenforms.dk
- Slack: #video-production channel
- GitHub Discussions: github.com/aabenforms/platform/discussions

**Need help with:**
- Equipment setup → README.md Equipment section
- Recording issues → RECORDING_CHECKLIST.md Troubleshooting
- Editing questions → README.md Post-Production section
- Publishing → README.md Publishing Workflow section

---

## License

All video scripts and production materials:
- **License**: CC BY-SA 4.0 (Creative Commons Attribution-ShareAlike)
- **You can**: Use, modify, share, adapt for your municipality
- **You must**: Give credit, share modifications under same license

---

**Status**: Pre-pilot POC. Scripts ready for review.
**Maintained by**: ÅbenForms Core Team
