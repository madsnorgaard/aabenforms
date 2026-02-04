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

**Purpose**: Introduction to ÅbenForms, value proposition, cost comparison
**Audience**: Municipal decision-makers, IT managers
**Key Messages**:
- Open source alternative to XFlow
- 90% cost savings
- No vendor lock-in
- Community-driven

**Script Includes**:
- Exact narration (Danish + English)
- Screen actions with timestamps
- Visual callouts and graphics
- Cost comparison chart
- Feature comparison table
- GitHub repository showcase
- Call to action

**Complexity**: ⭐ Simple (mostly slides and homepage)
**Recording Time**: ~1 hour
**Editing Time**: ~4 hours

---

### 2. Parking Permit Demo (5 minutes)
**File**: [02-PARKING_PERMIT_DEMO.md](02-PARKING_PERMIT_DEMO.md)

**Purpose**: End-to-end workflow demonstration (simple example)
**Audience**: Municipal staff, citizens, general public
**Key Messages**:
- 5 minutes from start to approved permit
- MitID authentication
- Payment processing
- Automatic PDF generation
- SMS confirmation
- 75% time savings vs manual process

**Script Includes**:
- Complete workflow walkthrough
- MitID login sequence
- Form completion (vehicle info, address)
- Payment processing (Nets)
- PDF license generation
- SMS notification
- Time comparison graphic

**Test Data Required**:
- Citizen: Jens Jensen (CPR: 0101701234)
- Vehicle: AB12345 (VW Golf)
- Payment: Test card 4111 1111 1111 1111
- Zone: Zone 2 - Nørregade området

**Complexity**: ⭐⭐ Medium (live demo with integrations)
**Recording Time**: ~2 hours
**Editing Time**: ~5 hours

---

### 3. Marriage Booking Demo (5 minutes)
**File**: [03-MARRIAGE_BOOKING_DEMO.md](03-MARRIAGE_BOOKING_DEMO.md)

**Purpose**: Complex dual-authentication workflow
**Audience**: Municipal registry office, engaged couples
**Key Messages**:
- Dual MitID authentication (both partners)
- Calendar integration
- Real-time availability
- Automated reminders (email + SMS)
- Complex workflow orchestration
- Celebratory, warm tone

**Script Includes**:
- Dual partner authentication flow
- Calendar and venue selection
- Witness information
- Payment and confirmation
- Automated reminder system
- Calendar invitation (.ics) download

**Test Data Required**:
- Partner A: Anna Andersen (CPR: 0101851234)
- Partner B: Bo Bertelsen (CPR: 0202881234)
- Witnesses: Carla Christensen, David Davidsen
- Venue: Rådhuset, Gammel Festsal
- Date: 3 weeks from recording date

**Complexity**: ⭐⭐⭐ Complex (dual auth, calendar, multiple integrations)
**Recording Time**: ~2.5 hours
**Editing Time**: ~6 hours

---

### 4. Building Permit Demo (7 minutes)
**File**: [04-BUILDING_PERMIT_DEMO.md](04-BUILDING_PERMIT_DEMO.md)

**Purpose**: Advanced features showcase (GIS, SBSYS, GDPR)
**Audience**: Municipal planners, building inspectors, architects
**Key Messages**:
- **GIS zoning validation** (XFlow doesn't have this!)
- Automatic neighbor notification
- Multi-stage approval workflow
- SBSYS integration
- GDPR compliance by design
- 75% faster than manual process

**Script Includes**:
- Project type selection
- MitID + property identification
- **GIS map visualization** (KEY FEATURE)
- **Automatic zoning validation** (KEY FEATURE)
- Document upload with validation
- **Automatic neighbor notification** (KEY FEATURE)
- BPMN workflow stages
- SBSYS integration
- GDPR compliance dashboard
- Building permit issuance

**Test Data Required**:
- Applicant: Mette Mortensen (CPR: 0101701234)
- Property: Skovvej 15 (ID: 751-12345)
- GIS data: Zone Residential, 3 neighbors identified
- Documents: 6 PDFs (plans, drawings, calculations)
- Contractor: Byg & Anlæg ApS (CVR: 98765432)

**Complexity**: ⭐⭐⭐⭐ Very Complex (GIS maps, multi-stage workflow, many integrations)
**Recording Time**: ~3 hours
**Editing Time**: ~8 hours

**NOTE**: This is the **most important video** - it showcases features XFlow doesn't have!

---

### 5. Visual Workflow Builder Tutorial (10 minutes)
**File**: [05-VISUAL_BUILDER_TUTORIAL.md](05-VISUAL_BUILDER_TUTORIAL.md)

**Purpose**: Demonstrate workflow creation without coding
**Audience**: Municipal IT staff, workflow designers, administrators
**Key Messages**:
- No coding required
- BPMN.io industry standard
- 16 Danish municipal task types
- Drag-and-drop interface
- Validation and testing
- Template gallery
- Community sharing

**Script Includes**:
- BPMN.io editor overview (3 panels)
- Building simple workflow from scratch (dog registration)
- Adding conditional logic (gateways)
- Danish municipal task palette (16 types)
- **GIS Validation task** (unique feature)
- **Neighbor Notification task** (unique feature)
- Validation and simulation
- Template gallery browsing
- Export/import workflows (BPMN XML)
- Advanced features (subprocess, timers, parallel gateways)

**Test Data Required**:
- Admin account: admin@demo.aabenforms.dk / demo123
- Workflow templates: 16+ in gallery
- BPMN.io editor configured with Danish palette

**Complexity**: ⭐⭐⭐ Complex (technical tutorial, requires clear instruction)
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

## Key Differentiators to Emphasize

These features make ÅbenForms superior to XFlow:

### 1. GIS Zoning Validation (Video 04)
- **What**: Automatic property identification and zoning validation
- **Why**: XFlow requires manual checking; we automate it
- **Impact**: Saves 2 hours per building permit application
- **Screen Time**: 90 seconds in Video 04 (1:45-3:00)

### 2. Automatic Neighbor Notification (Video 04)
- **What**: GIS-based neighbor identification and automated letters
- **Why**: XFlow requires manual neighbor lookup and letter writing
- **Impact**: Saves 1 hour per application, reduces errors
- **Screen Time**: 45 seconds in Video 04 (4:00-4:45)

### 3. Visual Workflow Builder (Video 05)
- **What**: Drag-and-drop BPMN workflow creation without coding
- **Why**: XFlow has limited workflow customization
- **Impact**: Non-technical staff can create new services
- **Screen Time**: Entire 10-minute video

### 4. Open Source & No Vendor Lock-in (Video 01)
- **What**: AGPL-3.0 licensed, self-hostable, community-driven
- **Why**: XFlow is proprietary, expensive, vendor lock-in
- **Impact**: 90% cost savings, full data sovereignty
- **Screen Time**: 45 seconds in Video 01 (2:15-2:45)

### 5. Danish Municipal Task Palette (Video 05)
- **What**: 16 pre-configured task types for Danish workflows
- **Why**: Generic BPM tools require extensive configuration
- **Impact**: 10x faster workflow creation
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

### Test Accounts
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
| 01 - Platform Overview |  | ⬜ | ⬜ | ⬜ | ⬜ |
| 02 - Parking Permit |  | ⬜ | ⬜ | ⬜ | ⬜ |
| 03 - Marriage Booking |  | ⬜ | ⬜ | ⬜ | ⬜ |
| 04 - Building Permit |  | ⬜ | ⬜ | ⬜ | ⬜ |
| 05 - Visual Builder |  | ⬜ | ⬜ | ⬜ | ⬜ |

**Legend**:  Complete | 🔄 In Progress | ⬜ Not Started

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

### YouTube Performance Targets (First Month)
- **Platform Overview**: 1,000+ views, 65%+ retention
- **Parking Permit**: 500+ views, 55%+ retention
- **Marriage Booking**: 400+ views, 55%+ retention
- **Building Permit**: 600+ views, 60%+ retention (key video)
- **Visual Builder**: 800+ views, 50%+ retention

### Engagement Targets
- Click-through rate: 4-10% (thumbnail quality)
- Like ratio: 95%+ (high quality indicator)
- Comments: 5-10 per video (engagement)
- Subscribers gained: 50-100 from series

### Business Impact Targets
- Demo requests: 10-20 from municipalities
- GitHub stars: +200 (from 1,247 to 1,450+)
- Downloads: 50-100 (self-hosted installations)
- Community members: +50 (forum, Discord)

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

**Last Updated**: 2024-02-02
**Version**: 1.0.0
**Status**: Ready for Production 
**Maintained by**: ÅbenForms Core Team
