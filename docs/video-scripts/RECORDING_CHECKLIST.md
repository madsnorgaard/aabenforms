# Video Recording Pre-Flight Checklist

Complete this checklist before recording each video to ensure smooth production and avoid common mistakes.

---

## General Setup (Before Every Recording)

### Environment Preparation

**Physical Space:**
- [ ] Quiet room with minimal background noise
- [ ] Close all windows (no traffic noise)
- [ ] Turn off HVAC/air conditioning (or wait for cycle to complete)
- [ ] Lock door / Put "Recording in Progress" sign up
- [ ] Close blinds/curtains (consistent lighting, no screen glare)
- [ ] Remove items that might make noise (phone, watch, keys)
- [ ] Have water nearby (stay hydrated, no ice/fizzy drinks)

**Computer Setup:**
- [ ] Fully charged laptop OR plugged into power
- [ ] Close all unnecessary applications (check Task Manager/Activity Monitor)
- [ ] Disable notifications (Do Not Disturb / Focus mode)
  - Windows: Settings → System → Focus → Priority only
  - macOS: Control Center → Focus → Do Not Disturb
  - Linux: Settings → Notifications → Do Not Disturb
- [ ] Disable auto-updates (Windows Update, macOS Software Update)
- [ ] Disable screen saver
- [ ] Disable sleep mode
- [ ] Set display brightness to 100% (consistent lighting)
- [ ] Clean desktop (hide icons, plain background or ÅbenForms wallpaper)
- [ ] Empty Downloads folder (avoid clutter in file dialogs)
- [ ] Clear browser cache and cookies
- [ ] Empty browser download history

**Audio Setup:**
- [ ] Microphone connected and recognized by system
- [ ] Test microphone in recording software (see levels -12dB to -6dB)
- [ ] Headphones connected (for monitoring, optional but recommended)
- [ ] Room tone test (record 10 seconds of silence, check for noise)
- [ ] Position microphone 6-12 inches from mouth (check manual)
- [ ] Pop filter installed (reduces plosives: P, B sounds)
- [ ] Microphone positioned slightly off to side (not directly in front of mouth)
- [ ] Disable desktop audio in recording software (no system sounds)

**Display Setup:**
- [ ] Set screen resolution to 1920x1080 (native if possible)
- [ ] Set scaling to 100% (no fractional scaling)
- [ ] Browser window maximized to full screen (or F11 full-screen mode)
- [ ] Second monitor for script/notes (if available)
  - Position script at eye level
  - Teleprompter software ready (optional)
- [ ] Color temperature consistent (disable auto-brightness, Night Shift/Night Light)

---

## Recording Software Configuration

### OBS Studio Settings (Recommended)

**Pre-Recording Checks:**
- [ ] OBS Studio open and profile loaded: "ÅbenForms Demos"
- [ ] Scene configured: "Browser Screen Capture"
- [ ] Source: Display Capture (full screen) or Window Capture (browser only)
- [ ] Audio sources:
  - Desktop Audio: MUTED (no system sounds)
  - Microphone: ENABLED, levels -12dB to -6dB
- [ ] Recording settings verified:
  - Output → Recording → Recording Path set (e.g., D:/ÅbenForms_Recordings/)
  - Output → Recording → Recording Format: MP4
  - Output → Recording → Encoder: x264 or NVENC (if available)
  - Output → Recording → Rate Control: CBR
  - Output → Recording → Bitrate: 12,000 Kbps
- [ ] Video settings verified:
  - Video → Base (Canvas) Resolution: 1920x1080
  - Video → Output (Scaled) Resolution: 1920x1080
  - Video → FPS: 30
- [ ] Audio settings verified:
  - Audio → Sample Rate: 48 kHz
  - Audio → Channels: Stereo
- [ ] Hotkeys configured:
  - Start Recording: F9 (or your preference)
  - Stop Recording: F10
  - Pause Recording: F11 (useful for breaks)

**Test Recording:**
- [ ] Record 30-second test clip
- [ ] Check video quality (sharp, not pixelated)
- [ ] Check audio quality (clear, no distortion)
- [ ] Check file location (recordings saving to correct folder)
- [ ] Check file size (should be ~20-30 MB per minute at 12,000 Kbps)
- [ ] Delete test file

### Alternative Software (Camtasia, ScreenFlow, etc.)

- [ ] Recording software open and configured
- [ ] Screen capture area set to 1920x1080 (full screen or window)
- [ ] Frame rate set to 30 fps
- [ ] Microphone input enabled and tested
- [ ] Desktop audio muted (no system sounds)
- [ ] Cursor effects enabled (optional: enlarge cursor, show clicks)
- [ ] Test recording completed and verified

---

## Browser Setup

**Browser Selection:**
- [ ] Chrome or Firefox (recommended for consistency)
- [ ] Use Incognito/Private mode (clean session, no extensions)
- [ ] If not incognito: Disable all extensions (especially ad blockers, privacy tools)

**Browser Configuration:**
- [ ] Clear cache (Ctrl+Shift+Del / Cmd+Shift+Del)
  - Time range: All time
  - Clear: Cached images and files, Cookies, Browsing history
- [ ] Clear downloads (so download bar doesn't show old files)
- [ ] Set zoom to 100% (Ctrl+0 / Cmd+0)
- [ ] Enable smooth scrolling (Settings → Advanced → System)
- [ ] Disable "Ask where to save each file before downloading"
  - Settings → Downloads → Automatically download to [folder]
- [ ] Close all tabs except demo environment
- [ ] Bookmark bar hidden (Ctrl+Shift+B / Cmd+Shift+B to toggle)
- [ ] Developer tools closed (F12 to toggle)

**Browser Performance:**
- [ ] Only 1-2 tabs open (demo site + script if needed)
- [ ] Hardware acceleration enabled (Settings → Advanced → System)
- [ ] GPU rendering enabled (chrome://gpu/)

---

## Demo Environment Setup

**General:**
- [ ] Demo environment URL accessible: https://demo.aabenforms.dk
- [ ] Homepage loads correctly (no errors in browser console)
- [ ] All navigation links work
- [ ] Test accounts prepared (see specific video checklists below)
- [ ] Test data loaded into database
- [ ] All integrations responding:
  - [ ] MitID test environment: accessible
  - [ ] Nets payment test gateway: accessible
  - [ ] SMS simulator: running
  - [ ] Email simulator: running
  - [ ] PDF generator: responding
  - [ ] (For Building Permit) GIS service: responding
  - [ ] (For Building Permit) SBSYS integration: mocked/responding

**System Clock:**
- [ ] Set to realistic time (e.g., 10:00 AM on a weekday)
- [ ] Consistent across all recordings (use same date for series)
- [ ] Suggested: Use February 2, 2024, 10:00 AM (a Friday)

**Database State:**
- [ ] Test workflows created and published
- [ ] Sample submissions exist (for showing history/status)
- [ ] Template gallery populated with 16 templates
- [ ] Admin account has proper permissions

---

## Video-Specific Checklists

### Video 01: Platform Overview (3 minutes)

**Assets Prepared:**
- [ ] ÅbenForms logo (high-res PNG)
- [ ] Cost comparison chart (saved as image or slide)
- [ ] Feature comparison table (HTML or image)
- [ ] GitHub repository at: https://github.com/aabenforms/platform (accessible)
- [ ] Mock statistics ready to show:
  - Stars: 1,247
  - Forks: 89
  - Contributors: 23

**Demo Environment:**
- [ ] Homepage loads with clean design
- [ ] Navigation menu shows all categories
- [ ] Workflow gallery shows 15+ workflows
- [ ] "Try Demo" button works

**Script & Narration:**
- [ ] Script printed or on second monitor
- [ ] Practice reading script 2-3 times (target: 3 minutes ±15 seconds)
- [ ] Highlight difficult words (practise pronunciation)
- [ ] Water nearby

---

### Video 02: Parking Permit Demo (5 minutes)

**Test Account:**
- [ ] Citizen MitID credentials ready:
  - CPR: 0101701234
  - Name: Jens Jensen
  - Address: Nørregade 12, 8000 Aarhus C
- [ ] Vehicle data ready:
  - Plate: AB12345
  - Make: Volkswagen
  - Model: Golf
  - Year: 2020
- [ ] Payment test card ready:
  - Number: 4111 1111 1111 1111
  - Expiry: 12/25
  - CVV: 123

**Demo Environment:**
- [ ] Parking permit workflow published and accessible
- [ ] MitID test environment responding
- [ ] Vehicle validation service mocked (returns VW Golf for AB12345)
- [ ] Zone data loaded (Zone 2 - Nørregade området)
- [ ] Zone map image available
- [ ] Payment gateway (Nets test) responding
- [ ] PDF generator configured with parking permit template
- [ ] SMS simulator ready to show notification

**Expected Flow Test:**
- [ ] Run through entire flow once as dry run
- [ ] Verify each step works:
  - [ ] MitID login succeeds
  - [ ] Address auto-fills from MitID
  - [ ] Vehicle validation works (AB12345 → VW Golf)
  - [ ] Zone selection shows Zone 2
  - [ ] Payment processes successfully
  - [ ] PDF generates correctly
  - [ ] SMS notification appears
- [ ] Total time: under 5 minutes

**Assets Ready:**
- [ ] Sample parking zone map (Zone 2 highlighted)
- [ ] Sample generated PDF (for preview)

---

### Video 03: Marriage Booking Demo (5 minutes)

**Test Accounts:**
- [ ] Partner A (Bride) MitID:
  - CPR: 0101851234
  - Name: Anna Andersen
  - Email: anna.andersen@example.dk
  - Phone: +45 20 12 34 56
- [ ] Partner B (Groom) MitID:
  - CPR: 0202881234
  - Name: Bo Bertelsen
  - Email: bo.bertelsen@example.dk
  - Phone: +45 30 12 34 56
- [ ] Witness 1:
  - CPR: 1510901234
  - Name: Carla Christensen
- [ ] Witness 2:
  - CPR: 2010851234
  - Name: David Davidsen

**Demo Environment:**
- [ ] Marriage booking workflow published
- [ ] Dual MitID authentication configured
- [ ] Calendar integration working:
  - [ ] Shows available dates (3 weeks from recording date)
  - [ ] Shows time slots with some blocked (realistic)
  - [ ] Target date: 3 weeks from today (e.g., February 23, 2024)
- [ ] Venue data loaded (3 ceremony locations):
  - [ ] Rådhuset, Gammel Festsal (selected in demo)
  - [ ] Rådhuset, Moderne Sal
  - [ ] Borgercenter, Glassal
- [ ] Venue photos available (3 images)
- [ ] Witness age validation working (auto-calculate from CPR)
- [ ] Payment gateway responding (1,200 kr)
- [ ] Email notification template ready
- [ ] Calendar invitation (.ics file) generates correctly

**Expected Flow Test:**
- [ ] Dual MitID login works (simulate both partners)
- [ ] Calendar shows available slots for target date
- [ ] Venue selection displays photos
- [ ] Witness validation works (age check)
- [ ] Payment processes
- [ ] Confirmation page shows all details
- [ ] Email and .ics file download works

**Assets Ready:**
- [ ] Venue photos (3 ceremony locations)
- [ ] Sample confirmation email
- [ ] Sample SMS reminder
- [ ] Automated reminder timeline graphic

---

### Video 04: Building Permit Demo (7 minutes)

**Test Account:**
- [ ] Applicant MitID:
  - CPR: 0101701234
  - Name: Mette Mortensen
  - Address: Skovvej 15, 8000 Aarhus C
  - Property ID: 751-12345
  - Email: mette.mortensen@example.dk
  - Phone: +45 40 12 34 56

**Demo Environment:**
- [ ] Building permit workflow published
- [ ] GIS service running with test data:
  - [ ] Property 751-12345 loaded
  - [ ] Property boundaries defined
  - [ ] Zone: Residential (green)
  - [ ] Neighboring properties defined (3 neighbors):
    - Skovvej 13 (751-12344) - 8.2m west
    - Skovvej 17 (751-12346) - 7.9m east
    - Parkvej 22 (751-12401) - 22.5m south
  - [ ] Zoning rules configured:
    - Max building coverage: 30%
    - Max height: 8.5m
    - Min distance to boundary: 2.5m
- [ ] GIS map rendering correctly:
  - [ ] Property highlighted
  - [ ] Zoning overlay visible (color-coded)
  - [ ] Neighbor properties outlined
- [ ] Automatic validation rules configured:
  - [ ] Building coverage calculation (existing 22%, with addition 25.3%)
  - [ ] Distance to boundaries (all > 2.5m)
  - [ ] Height check (6.2m < 8.5m)
- [ ] Document upload working (accepts PDF, DWG files)
- [ ] Sample architectural drawings ready:
  - [ ] situationsplan.pdf (1.2 MB)
  - [ ] plantegninger.pdf (2.5 MB)
  - [ ] facade.pdf (1.8 MB)
  - [ ] snit.pdf (1.1 MB)
  - [ ] beregninger.pdf (0.8 MB)
  - [ ] 3d-visualisering.jpg (4.2 MB)
- [ ] Contractor information ready:
  - CVR: 98765432
  - Name: Byg & Anlæg ApS
- [ ] Neighbor notification system ready:
  - [ ] Auto-identifies 3 neighbors via GIS
  - [ ] Generates notification letters
  - [ ] Tracks delivery status
- [ ] BPMN workflow visualization ready (5 stages)
- [ ] SBSYS integration mock responding
- [ ] GDPR compliance dashboard accessible

**Expected Flow Test:**
- [ ] MitID login retrieves property data
- [ ] GIS map displays property correctly
- [ ] Automatic validation runs and passes all checks
- [ ] Document upload accepts all files
- [ ] Neighbor notification identifies 3 neighbors correctly
- [ ] Workflow stages display correctly
- [ ] Total time: under 7 minutes

**Assets Ready:**
- [ ] GIS zoning map (color-coded, legend)
- [ ] Property cadastral map
- [ ] Sample architectural drawings (6 PDFs)
- [ ] Neighbor notification email template
- [ ] BPMN workflow diagram (5 stages)
- [ ] SBSYS integration flow diagram
- [ ] GDPR access log mockup
- [ ] Building permit PDF template
- [ ] Construction site sign (A3 poster) PDF

---

### Video 05: Visual Builder Tutorial (10 minutes)

**Admin Account:**
- [ ] Username: admin@demo.aabenforms.dk
- [ ] Password: demo123
- [ ] Role: Workflow Designer (full permissions)

**Demo Environment:**
- [ ] Admin dashboard accessible
- [ ] Workflow builder loads correctly: /admin/workflow-builder
- [ ] BPMN.io editor configured with Danish palette
- [ ] 16 task types available in palette:
  - [ ] Start Event
  - [ ] MitID Authentication
  - [ ] Form Task
  - [ ] Validation Task
  - [ ] Payment Task
  - [ ] Email Task
  - [ ] SMS Task
  - [ ] PDF Generation
  - [ ] GIS Validation
  - [ ] Neighbor Notification
  - [ ] Integration Task
  - [ ] User Task
  - [ ] Service Task
  - [ ] Gateway (XOR/AND/OR/Event)
  - [ ] Subprocess
  - [ ] End Event
- [ ] Properties panel working (right side)
- [ ] Canvas responsive (zoom, pan working)
- [ ] Template gallery populated with 16+ templates
- [ ] Validation service responding
- [ ] Simulation/test mode working
- [ ] Export functionality working (BPMN XML, JSON, SVG, PNG)
- [ ] Import functionality working

**Expected Flow Test:**
- [ ] Can create new workflow from scratch
- [ ] Can drag tasks from palette to canvas
- [ ] Can configure task properties
- [ ] Can connect tasks with sequence flows
- [ ] Can add gateway (XOR) with conditions
- [ ] Validation runs and shows results
- [ ] Simulation mode works (token animation)
- [ ] Can save workflow to gallery
- [ ] Can export as BPMN XML
- [ ] Total time: under 10 minutes

**Assets Ready:**
- [ ] BPMN.io editor screenshot (three panels labeled)
- [ ] Task palette icon reference sheet
- [ ] Sample workflows (simple, medium, complex)
- [ ] Workflow diagram examples
- [ ] Validation results mockup
- [ ] BPMN XML code snippet (formatted)

---

## Script & Narration Preparation

**Script Review:**
- [ ] Read script out loud 2-3 times
- [ ] Time yourself (should match target duration ±10%)
- [ ] Highlight difficult words or Danish terms
- [ ] Practice pronunciation of Danish words (if non-native speaker)
- [ ] Mark breathing points (add pauses in script)
- [ ] Mark emphasis points (words to stress)

**Narration Setup:**
- [ ] Script visible on second monitor OR printed
- [ ] Teleprompter software ready (optional but helpful)
- [ ] Large, readable font (18pt+)
- [ ] High contrast (black text on white background)
- [ ] Script scrolls smoothly (if using teleprompter)

**Voice Preparation:**
- [ ] Well-rested (avoid recording when tired)
- [ ] Hydrated (drink water 30 minutes before)
- [ ] Vocal warm-up exercises (hum, lip trills, scales)
- [ ] Avoid dairy, caffeine immediately before (can cause mouth noise)
- [ ] Avoid recording if sick (stuffy nose affects voice)

---

## Recording Best Practices Reminder

**During Recording:**
- [ ] Smile while narrating (comes through in voice tone)
- [ ] Speak at 90% of normal speed (clearer, easier to follow)
- [ ] Pause 2 seconds before and after major actions
- [ ] Move mouse slowly and deliberately
- [ ] Wait for hover effects to complete before clicking
- [ ] Wait for animations to finish before moving on
- [ ] Let loading spinners complete (adds realism)
- [ ] Type at moderate speed (viewers need to see what you type)
- [ ] If you make a mistake:
  - Don't stop recording
  - Pause 3 seconds
  - Repeat the sentence or action
  - Fix in editing later

**Pacing:**
- [ ] Don't rush through sections
- [ ] Pause after important points (give viewers time to absorb)
- [ ] Match narration to on-screen actions (not too far ahead or behind)
- [ ] If demo is slower than narration, pause narration (don't talk over loading screens)

**Common Mistakes to Avoid:**
- [ ] Don't narrate and type simultaneously (record separately or pause)
- [ ] Don't use filler words (um, uh, så, altså)
- [ ] Don't apologize for mistakes (just redo the section)
- [ ] Don't click before explaining what you're clicking
- [ ] Don't scroll too fast (viewers can't read)
- [ ] Don't skip over errors (if something fails, acknowledge and fix)

---

## Post-Recording Checklist

**Immediate Actions:**
- [ ] Save recording file with clear naming:
  - Format: `[Video#]_[Title]_[Take#]_[Date].mp4`
  - Example: `02_ParkingPermit_Take1_2024-02-02.mp4`
- [ ] Verify file saved correctly (not corrupted)
- [ ] Watch first 30 seconds and last 30 seconds (quick quality check)
- [ ] Check audio levels in editor (-6dB to -3dB peak)
- [ ] Check video quality (sharp, not pixelated)
- [ ] Make notes of any issues to fix in editing

**Backup:**
- [ ] Copy recording file to backup location:
  - Cloud storage (Google Drive, Dropbox, OneDrive)
  - External hard drive
  - Network storage
- [ ] Keep raw recordings until project complete (don't delete after editing)

**Notes for Editing:**
- [ ] Create editing notes document:
  - Timestamp: 2:34 - Minor stutter, re-record narration
  - Timestamp: 5:12 - Loading took too long, speed up in editing
  - Timestamp: 7:45 - Add zoom effect on this section
- [ ] Note any graphics that need to be created
- [ ] Note any sections that need re-recording

---

## Emergency Troubleshooting

**If recording stops unexpectedly:**
- [ ] Check disk space (need 10GB+ free)
- [ ] Check recording software didn't crash
- [ ] Restart recording software
- [ ] Record test clip to verify it's working
- [ ] Resume recording from last good section (note timestamp)

**If audio quality is poor:**
- [ ] Check microphone connection
- [ ] Check audio input levels (not too low, not clipping)
- [ ] Check for background noise (turn off HVAC)
- [ ] Move microphone closer (6-8 inches from mouth)
- [ ] Check pop filter is in place

**If demo environment fails:**
- [ ] Refresh browser (F5)
- [ ] Clear cache and restart browser
- [ ] Check internet connection
- [ ] Check if backend services are running
- [ ] Use backup test account (if primary fails)
- [ ] Worst case: Pause recording, fix issue, resume

**If you're not feeling it:**
- [ ] Take a 10-minute break
- [ ] Stretch, walk around
- [ ] Drink water
- [ ] Re-read script
- [ ] Remember: You can fix mistakes in editing
- [ ] If really struggling: Reschedule for another day (better to wait than force poor quality)

---

## Final Pre-Recording Check (Right Before Pressing Record)

**3-2-1 Check:**
- [ ] **3 minutes**: Review script one last time
- [ ] **2 minutes**: Test record 15 seconds, verify audio/video quality
- [ ] **1 minute**: Take deep breath, relax shoulders, smile
- [ ] **GO**: Press record (F9 in OBS) and wait 3 seconds before starting narration

**Green Light Checklist (All Must Be YES):**
- [ ] Environment quiet? YES / NO
- [ ] Notifications disabled? YES / NO
- [ ] Recording software ready? YES / NO
- [ ] Demo environment working? YES / NO
- [ ] Script visible? YES / NO
- [ ] Microphone tested? YES / NO
- [ ] Feeling prepared? YES / NO

If any answer is NO, fix it before recording.

---

## Post-Session Debrief

After completing recording session, answer these questions:

**What went well?**
-

**What could be improved?**
-

**Technical issues encountered?**
-

**Sections that need re-recording?**
-

**Notes for next recording session?**
-

**Estimated editing time needed?**
-

---

## Sign-Off

**Video**: _______________
**Date Recorded**: _______________
**Recorded By**: _______________
**Total Takes**: _______________
**Best Take**: _______________
**Ready for Editing**: YES / NO
**Notes**:
_______________________________________________
_______________________________________________
_______________________________________________

---

**Template Version**: 1.0.0
**Last Updated**: 2024-02-02
