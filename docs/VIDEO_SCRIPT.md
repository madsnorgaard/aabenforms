# ÅbenForms Video Tutorial Script

**Video Title**: Creating Your First Workflow in ÅbenForms
**Duration**: 5 minutes
**Target Audience**: Municipal administrators (non-technical)
**Format**: Screen recording with voiceover

---

## Scene 1: Introduction (30 seconds)

### Visual
- ÅbenForms logo
- Fade to dashboard homepage
- Highlight key sections

### Script

> "Velkommen til ÅbenForms, en moderne workflow-platform til danske kommuner."
>
> "I denne video lærer du at oprette dit første godkendelsesflow på under 5 minutter."
>
> "Vi bruger eksemplet med dagtilbudsindskrivning, hvor begge forældre skal godkende."
>
> "Lad os komme i gang!"

**[Translation for international audiences]:**
"Welcome to ÅbenForms, a modern workflow platform for Danish municipalities. In this video, you'll learn to create your first approval workflow in under 5 minutes. We'll use a daycare enrollment example where both parents must approve. Let's get started!"

### On-Screen Text
```
 No technical knowledge required
 Pre-built templates available
 GDPR compliant by default
```

---

## Scene 2: Creating Workflow from Template (2 minutes)

### Visual
- Navigate to workflow admin page
- Show template selection screen
- Fill out wizard steps

### Script

> "Først skal vi vælge en skabelon. Klik på 'Konfiguration', derefter 'Workflow', og 'ECA'."
>
> [PAUSE - Show navigation]
>
> "Her ser vi fem forudbyggede skabeloner til typiske kommunale processer."
>
> [HIGHLIGHT each template briefly]
>
> "Vi vælger 'Building Permit'-skabelonen, fordi den har den dobbelte godkendelsesflow vi skal bruge."
>
> [CLICK on Building Permit template]
>
> "Klik 'Brug Denne Skabelon'."
>
> [PAUSE at wizard step 1]
>
> "Nu gennemgår vi en trin-for-trin guide med 8 enkle trin."

### Wizard Step 1: Basic Information

**On-Screen**:
```
Workflow ID: dagtilbud_indskrivning_aarhus
Workflow Label: Dagtilbudsindskrivning - Aarhus Kommune
Description: Begge forældre skal godkende dagtilbudspladsen
```

> "Første trin: Giv din workflow et unikt ID og en beskrivelse."
>
> [TYPE while speaking]
>
> "ID'et skal være unikt og kun indeholde små bogstaver og underscores."

### Wizard Step 2: Webform Selection

**On-Screen**:
```
Select Existing Form: [dagtilbud_form]
```

> "Vælg den webformular, der skal udløse workflowet."
>
> [SELECT from dropdown]
>
> "Hvis formularen ikke findes, kan vi oprette en ny her."

### Wizard Step 3: Authentication

**On-Screen**:
```
 Require MitID Authentication
Authentication Level: High
Who Must Authenticate:
   Parent 1
   Parent 2
```

> "Vi kræver MitID-godkendelse med højt sikkerhedsniveau for begge forældre."
>
> [CHECK boxes while explaining]

### Wizard Step 4: Approval Flow

**On-Screen**:
```
Approval Pattern: Parallel
Timeout: 14 days
Both Parents Must Approve: Yes
```

> "Forældrene kan godkende parallelt – altså samtidig. Efter 14 dage udløber anmodningen automatisk."
>
> [SELECT options while speaking]

### Wizard Step 5: Notifications

**On-Screen**:
```
 Email
 Digital Post
Data Visibility: Parents Living Apart → Limited
```

> "System sender både email og Digital Post til forældrene."
>
> "Hvis forældrene bor hver for sig, sikrer vi begrænset datasynlighed for GDPR-compliance."
>
> [HOVER over GDPR info icon]

### Wizard Step 6-8: Quick Overview

> "De sidste trin konfigurerer sagsbehandler-gennemgang, systemintegrationer, og GDPR-indstillinger."
>
> [QUICKLY scroll through remaining steps]
>
> "Systemet foreslår fornuftige standardværdier, så vi kan bare klikke 'Næste'."
>
> [CLICK Next repeatedly]

### Final Step: Review

**On-Screen**:
```
 Workflow ID: dagtilbud_indskrivning_aarhus
 Authentication: MitID High
 Approval Flow: Parallel, both parents
 Notifications: Email + Digital Post
 GDPR: Fully compliant
```

> "Her er en oversigt over vores workflow. Alt ser godt ud!"
>
> [PAUSE to let viewers read]
>
> "Bemærk: Vi opretter som 'Draft' først, så vi kan teste før aktivering."
>
> [CLICK "Create Workflow (as Draft)"]

---

## Scene 3: Testing the Approval Flow (1.5 minutes)

### Visual
- Switch to test tab
- Fill out test form
- Show email notifications
- Parent 1 approves
- Parent 2 approves
- Case worker reviews

### Script

> "Nu tester vi workflowet før vi går live."
>
> [CLICK on "Test Workflow" tab]
>
> "System opretter testdata automatisk."
>
> [SHOW test data being generated]

### Submitting Test Form

**On-Screen**:
```
Child: Test Barn
CPR: 150320-9999
Parent 1: test-parent1@aarhus.dk
Parent 2: test-parent2@aarhus.dk
```

> "Vi udfylder formularen med testdata og klikker 'Send Test'."
>
> [TYPE and SUBMIT]

### Parent 1 Approval (Split Screen)

**Visual**: Show email inbox + approval page side-by-side

> "Forælder 1 modtager en email inden for 1 minut."
>
> [SHOW email arriving in inbox]
>
> "De klikker på linket, logger ind med MitID, og ser informationen."
>
> [SHOW approval page loading]
>
> "Bemærk: De ser alle oplysninger, fordi forældrene bor sammen."
>
> [HIGHLIGHT data visibility]
>
> "De klikker 'Jeg Godkender'."
>
> [CLICK approve button]

### Parent 2 Approval (Quick)

> "Samme proces for Forælder 2."
>
> [FAST-FORWARD through same steps]
>
> "Begge forældre har nu godkendt."

### Case Worker Review

**On-Screen**:
```
Task Queue:
  Dagtilbudsindskrivning - Test Barn
  Status: Awaiting Review
  Parent 1:  Approved (2026-02-02 09:30)
  Parent 2:  Approved (2026-02-02 11:20)
```

> "Nu ser sagsbehandleren opgaven i deres kø."
>
> [SHOW task list]
>
> "De gennemgår begge godkendelser og beslutninger."
>
> [SCROLL through timeline]
>
> "De godkender, og systemet opretter automatisk sagen i SBSYS."
>
> [CLICK "Approve and Create Case"]

### Success Screen

**On-Screen**:
```
 Test Completed Successfully
 Both parents approved
 Case worker approved
 SBSYS case created (#98765)
 Digital Post sent to both parents
 All actions logged
```

> "Succes! Workflowet fungerer perfekt."
>
> [SHOW checkmarks appearing one by one]
>
> "Hele processen tog under 5 minutter."

---

## Scene 4: Monitoring and Management (1 minute)

### Visual
- Dashboard overview
- Pending tasks view
- Audit log view

### Script

> "Lad os se hvordan du overvåger workflowet i drift."

### Dashboard

**On-Screen**: Show dashboard with real-time metrics

```
Dashboard:
  Pending Approvals: 12
  Completed Today: 45
  Average Response Time: 18 hours
```

> "Dashboard'et viser status for alle igangværende godkendelser."
>
> [HIGHLIGHT key metrics]
>
> "Vi kan se ventende opgaver, gennemførte workflows, og svartider."

### Pending Tasks

**Visual**: Show task list

> "Her er alle opgaver der venter på handling."
>
> [SCROLL through task list]
>
> "Vi kan filtrere efter status, dato, eller sagsbehandler."
>
> [SHOW filters]

### Audit Log

**Visual**: Show audit log

```
Audit Log:
  2026-02-02 09:30 - Parent 1 authenticated (MitID High)
  2026-02-02 09:31 - CPR lookup (SF1520)
  2026-02-02 09:32 - Parent 1 approved
  ...
```

> "Alle handlinger logges automatisk for GDPR-compliance."
>
> [SCROLL through log entries]
>
> "Vi kan eksportere logs til CSV for rapportering."

### Going Live

> "Når testen er succesfuld, aktiverer vi workflowet."
>
> [NAVIGATE back to workflow list]
>
> "Ændrer status fra 'Draft' til 'Active'."
>
> [CHANGE status dropdown: Draft → Active]
>
> "Bekræft aktiveringen."
>
> [CLICK confirm]

**On-Screen**:
```
 Workflow Activated
 Now accepting live submissions
 Citizens can submit at: /form/dagtilbud-aarhus
```

> "Og nu er workflowet live!"

---

## Scene 5: Closing (30 seconds)

### Visual
- Show final workflow diagram
- Display key benefits
- Show resources links

### Script

> "Tillykke! Du har nu oprettet, testet, og aktiveret dit første godkendelsesflow."
>
> [SHOW workflow diagram animating through]

**On-Screen**:
```
What You've Learned:
   Choose the right template
   Configure with the wizard
   Test before going live
   Monitor active workflows
```

> "ÅbenForms gør det nemt at automatisere kommunale processer uden teknisk viden."
>
> [SHOW benefits list]

**On-Screen**:
```
Key Benefits:
  • Pre-built templates
  • GDPR compliant by default
  • MitID integration
  • Real-time monitoring
  • Full audit trails
```

> "Vil du lære mere? Se vores dokumentation, deltag i webinarer, eller kontakt support."
>
> [SHOW resources]

**On-Screen**:
```
Resources:
  📖 Documentation: aabenforms.dk/docs
  🎓 Training: aabenforms.dk/training
  💬 Community: aabenforms.dk/forum
  📧 Support: support@aabenforms.dk
```

> "Tak fordi du så med, og held og lykke med jeres workflows!"
>
> [FADE to logo]

---

## Post-Production Notes

### Editing

**Add these elements**:
- ÅbenForms logo watermark (bottom right)
- Step counters (Step 1/4, 2/4, etc.)
- Keyboard shortcut overlays when applicable
- Mouse click animations for clarity
- Progress bars for waiting steps

**Transitions**:
- Fade between scenes (0.5 seconds)
- Zoom in on important UI elements
- Highlight mouse clicks with circles

**Pacing**:
- Speak clearly at ~150 words per minute
- Pause 2 seconds after each key action
- Allow 3 seconds for viewers to read on-screen text

### Subtitles

Provide subtitles in:
- **Danish** (primary)
- **English** (secondary)

### Video Formats

Export in:
- **1080p** (primary, for website)
- **720p** (mobile-optimized)
- **GIF** (key moments for documentation)

### Thumbnail

Create thumbnail with:
- ÅbenForms logo
- Text: "Create Your First Workflow in 5 Minutes"
- Screenshot of workflow wizard
- Play button overlay

---

## Alternative Versions

### Extended Version (15 minutes)

Add these sections:
- Detailed explanation of each wizard step
- More complex approval scenarios
- Troubleshooting common issues
- Advanced customization options

### Quick Start (2 minutes)

Focus only on:
- Selecting template
- Naming workflow
- Testing
- Activating

### Advanced Topics (Series)

Create separate videos for:
- **Video 2**: Customizing Templates (10 min)
- **Video 3**: GDPR Compliance in Depth (8 min)
- **Video 4**: Multi-System Integrations (12 min)
- **Video 5**: Reporting and Analytics (7 min)

---

## Recording Checklist

### Before Recording

- [ ] Test workflow works as expected
- [ ] Prepare test data
- [ ] Clear browser cache and cookies
- [ ] Set browser zoom to 100%
- [ ] Hide browser bookmarks bar
- [ ] Close unnecessary tabs
- [ ] Disable browser notifications
- [ ] Set screen resolution to 1920x1080

### Equipment

- [ ] High-quality microphone
- [ ] Quiet recording environment
- [ ] Screen recording software (OBS, Camtasia)
- [ ] Audio editing software (Audacity)
- [ ] Video editing software (DaVinci Resolve, Premiere)

### Practice Runs

- [ ] Record full dry run
- [ ] Review for timing
- [ ] Check audio levels
- [ ] Verify all clicks visible
- [ ] Ensure smooth transitions

---

## Distribution

### Publishing Platforms

- **Primary**: ÅbenForms website (aabenforms.dk/tutorials)
- **Secondary**: YouTube (ÅbenForms channel)
- **Tertiary**: Municipal intranet portals

### Promotion

- Email to existing users
- Social media (LinkedIn, Twitter)
- Municipal IT administrator newsletters
- Conference presentations

### Analytics to Track

- View count
- Watch time (average)
- Drop-off points
- Viewer feedback/comments
- Support ticket reduction (if video helpful)

---

## Updates and Maintenance

**Review video every 6 months** or when:
- Major UI changes
- New features added
- User feedback suggests improvements
- Significant bug fixes change workflow

**Version the video**:
- Title: "Creating Your First Workflow (v1.0)"
- Description: "Updated: 2026-02-02"
- Link to latest version

---

**This video script is ready for recording by your municipality or ÅbenForms team!**
