# Video Script 05: Visual Workflow Builder Tutorial (10 minutes)

## Video Metadata
- **Duration**: 10 minutes
- **Target Audience**: Municipal IT staff, system administrators, workflow designers
- **Goal**: Demonstrate workflow creation without coding
- **Tone**: Educational, empowering, technical-but-accessible

---

## Technical Setup

### Recording Environment
- **Browser**: Chrome with workflow builder tools
- **Resolution**: 1920x1080 (important for seeing workflow canvas)
- **Screen recording**: 30fps, high quality for UI interactions
- **Test environment**: https://demo.aabenforms.dk/admin/workflow-builder

### Admin Account Credentials
- Username: admin@demo.aabenforms.dk
- Password: demo123
- Role: Workflow Designer

### Pre-recording Checklist
- [ ] Admin dashboard configured
- [ ] BPMN.io editor loaded with Danish palette
- [ ] Template gallery populated (16 task types)
- [ ] Sample workflow prepared for modification
- [ ] Test workflow engine connection
- [ ] Enable auto-save feature
- [ ] Configure validation service
- [ ] Prepare export/import demonstration

### Visual Assets Needed
- BPMN.io editor interface screenshots
- Danish municipal task palette icons (16 types)
- Workflow template gallery
- Sample workflows (simple, medium, complex)
- BPMN XML code snippet
- Validation error examples

---

## SCRIPT

### [0:00-0:30] Introduction (30 seconds)

#### NARRATION (Danish)
"Velkommen til tutorial om ÅbenForms Visual Workflow Builder. I denne video lærer du at oprette selvbetjeningsworkflows uden at skrive en eneste linje kode. Vi bruger BPMN.io - industristandarden for business process modellering - udvidet med en dansk kommunal task-palette specifikt designet til danske selvbetjeningsløsninger. Lad os bygge en workflow fra bunden."

#### NARRATION (English)
"Welcome to the ÅbenForms Visual Workflow Builder tutorial. In this video, you'll learn to create self-service workflows without writing a single line of code. We use BPMN.io - the industry standard for business process modeling - extended with a Danish municipal task palette specifically designed for Danish self-service solutions. Let's build a workflow from scratch."

#### SCREEN ACTIONS
1. Start at admin dashboard: https://demo.aabenforms.dk/admin
2. Show navigation menu
3. Click "Workflow Builder"
4. Show workflow gallery landing page with existing workflows:
   - Parkeringslicens (Simple)
   - Vielse (Medium)
   - Byggetilladelse (Complex)
   - + 13 more templates
5. Click "Opret ny workflow"

#### VISUAL CALLOUTS
- Highlight "Workflow Builder" menu item
- Show workflow complexity indicators
- Display "16 danske task-typer" badge
- Show "Ingen kodning påkrævet" badge
- Highlight BPMN.io logo (industry standard)

#### RECORDING TIPS
- Confident, educational tone
- Emphasize "no coding required"
- Show template gallery briefly
- Professional instructor voice
- Background music: subtle, technical

---

### [0:30-1:30] BPMN.io Editor Overview (60 seconds)

#### NARRATION (Danish)
"Dette er BPMN.io-editoren. På venstre side ser du den danske kommunale task-palette med 16 forskellige opgavetyper. I midten er canvas'et, hvor du bygger din workflow. Højre side viser egenskabspanelet, hvor du konfigurerer hver opgave. Lad os starte med en simpel workflow: Hunderegistrering."

#### NARRATION (English)
"This is the BPMN.io editor. On the left side, you see the Danish municipal task palette with 16 different task types. In the center is the canvas where you build your workflow. The right side shows the properties panel where you configure each task. Let's start with a simple workflow: Dog Registration."

#### SCREEN ACTIONS
1. Show BPMN.io editor interface with three panels:

   **Left Panel - Task Palette (Værktøjskasse):**
   - 📋 Start Event (Start)
   - 🔐 MitID Authentication
   -  Form Task (Formular)
   -  Validation Task (Validering)
   - 💳 Payment Task (Betaling)
   - 📧 Email Task (Email)
   - 📱 SMS Task (SMS)
   - 📄 PDF Generation (PDF-generering)
   - 🗺️ GIS Validation (GIS-validering)
   - 📨 Neighbor Notification (Naboorientering)
   - 🔄 Integration Task (Integration)
   - 👤 User Task (Manual opgave)
   - ⚙️ Service Task (Automatisk opgave)
   - ❓ Gateway (Beslutning)
   -  Subprocess (Underproces)
   - 🏁 End Event (Slut)

   **Center Panel - Canvas:**
   - Grid background
   - Zoom controls (25%, 50%, 100%, 200%)
   - Pan/zoom enabled
   - Currently empty

   **Right Panel - Properties (Egenskaber):**
   - Workflow name: [Empty]
   - Description: [Empty]
   - Version: 1.0.0
   - Category: [Empty]

2. Name the workflow:
   - Workflow navn: "Hunderegistrering"
   - Beskrivelse: "Registrering af ny hund i hunderegistret"
   - Kategori: "Natur & Miljø"
   - Ikon: 🐕 (dog icon)

#### VISUAL CALLOUTS
- Box each panel with label overlay
- Highlight task palette with count: "16 task-typer"
- Show canvas grid structure
- Highlight zoom controls
- Circle properties panel
- Show task icons with Danish labels
- Display color coding:
  - Blue: User interactions
  - Green: Automated tasks
  - Orange: Integrations
  - Red: Gateways/decisions

#### RECORDING TIPS
- Pan camera across interface systematically
- Point to each panel as you mention it
- Emphasize the Danish-specific palette
- Professional, tutorial-style delivery
- Pause on task palette to let viewers see options

---

### [1:30-3:30] Building a Simple Workflow (120 seconds)

#### NARRATION (Danish)
"Lad os bygge hunderegistrerings-workflow'en trin for trin. Først trækker vi en Start Event ud på canvas. Derefter tilføjer vi MitID Authentication - fordi vi skal vide, hvem der registrerer hunden. Så kommer en Form Task til hundens oplysninger. En Payment Task til registreringsgebyret. En PDF Generation til registreringsbeviset. Og til sidst en End Event. Lad os forbinde dem."

#### NARRATION (English)
"Let's build the dog registration workflow step by step. First, we drag a Start Event onto the canvas. Then we add MitID Authentication - because we need to know who is registering the dog. Then a Form Task for the dog's information. A Payment Task for the registration fee. A PDF Generation for the registration certificate. And finally an End Event. Let's connect them."

#### SCREEN ACTIONS

**Step 1: Add Start Event (0:00-0:15)**
1. Drag "📋 Start Event" from palette to canvas
2. Drop in center-top of canvas
3. Properties panel auto-opens:
   - Label: "Borger starter registrering"
   - Trigger type: "Form submission"
   - Form URL: /hunderegistrering

**Step 2: Add MitID Authentication (0:15-0:30)**
1. Drag "🔐 MitID Authentication" to canvas
2. Place below Start Event
3. Configure properties:
   - Label: "Log ind med MitID"
   - Assurance level: "Substantial"
   - Attributes to fetch:
     -  CPR number
     -  Name
     -  Address
   - Error handling: "Show error message"

**Step 3: Connect Start to MitID (0:30-0:35)**
1. Click Start Event
2. Click connection arrow icon
3. Drag to MitID task
4. Sequence flow created automatically

**Step 4: Add Form Task (0:35-0:50)**
1. Drag " Form Task" to canvas
2. Place below MitID
3. Configure properties:
   - Label: "Hundens oplysninger"
   - Form schema:
     - Hundens navn (text, required)
     - Race (dropdown, required)
     - Fødselsdato (date, required)
     - Chipnummer (text, 15 digits)
     - Farve (text)
     - Køn (radio: Han/Hun, required)
   - Validation: All required fields
4. Connect MitID → Form Task

**Step 5: Add Payment Task (0:50-1:10)**
1. Drag "💳 Payment Task" to canvas
2. Configure properties:
   - Label: "Betaling af registreringsgebyr"
   - Provider: "Nets"
   - Amount: 150 kr (fixed)
   - Description: "Hunderegistrering 2024"
   - Receipt required: Yes
   - On failure: "Return to form"
3. Connect Form Task → Payment Task

**Step 6: Add PDF Generation (1:10-1:30)**
1. Drag "📄 PDF Generation" to canvas
2. Configure properties:
   - Label: "Generer registreringsbevis"
   - Template: "dog_registration_certificate"
   - Data sources:
     - Owner: ${mitid.name}
     - Dog name: ${form.dogName}
     - Breed: ${form.breed}
     - Birth date: ${form.birthDate}
     - Chip number: ${form.chipNumber}
     - Registration fee: ${payment.amount}
   - Output: "registration_certificate.pdf"
3. Connect Payment Task → PDF Generation

**Step 7: Add Email Task (1:30-1:50)**
1. Drag "📧 Email Task" to canvas
2. Configure properties:
   - Label: "Send bekræftelse"
   - To: ${mitid.email}
   - Subject: "Din hund er nu registreret"
   - Template: "dog_registration_confirmation"
   - Attachments:
     - ${pdf.registration_certificate}
   - CC: hunderegistret@aarhus.dk
3. Connect PDF Generation → Email Task

**Step 8: Add End Event (1:50-2:00)**
1. Drag "🏁 End Event" to canvas
2. Configure properties:
   - Label: "Registrering gennemført"
   - Completion message: "Din hund er nu registreret!"
   - Redirect URL: /min-side/hunde
3. Connect Email Task → End Event

#### VISUAL CALLOUTS
- Show drag animation for each task
- Highlight properties panel as each task is configured
- Display variable syntax highlighting: `${mitid.name}`
- Show connection arrows being drawn
- Animate task labels appearing
- Box the completed workflow
- Show linear flow: Start → MitID → Form → Payment → PDF → Email → End

#### RECORDING TIPS
- Drag tasks slowly and deliberately
- Pause on each properties panel (5 seconds)
- Explain variable syntax: "Notice the ${} notation"
- Type configuration values at moderate speed
- Show cursor movements clearly
- Emphasize the visual nature: "No code needed!"

---

### [3:30-5:00] Adding Conditional Logic with Gateways (90 seconds)

#### NARRATION (Danish)
"Nu gør vi workflow'en mere intelligent med en beslutningsgateway. Nogle hunderacer kræver særlig godkendelse fra kommunen. Lad os tilføje logik, så farlige hunderacer går til manuel godkendelse, mens almindelige racer godkendes automatisk."

#### NARRATION (English)
"Now let's make the workflow more intelligent with a decision gateway. Some dog breeds require special approval from the municipality. Let's add logic so dangerous dog breeds go to manual approval, while regular breeds are automatically approved."

#### SCREEN ACTIONS

**Step 1: Insert Gateway (0:00-0:20)**
1. Click on connection between Form Task and Payment Task
2. Click "Insert" icon
3. Select "❓ Exclusive Gateway (XOR)"
4. Gateway inserted in flow
5. Configure properties:
   - Label: "Kræver særlig godkendelse?"
   - Decision variable: ${form.breed}
   - Type: "Exclusive (XOR)"

**Step 2: Configure Dangerous Breed Path (0:20-0:50)**
1. Drag new connection from Gateway
2. Add "👤 User Task" to canvas
3. Configure User Task:
   - Label: "Manuel godkendelse af farlig hund"
   - Assignee: "Natur & Miljø afdeling"
   - Due date: 3 arbejdsdage
   - Form fields:
     - Godkend ansøgning? (radio: Ja/Nej)
     - Begrundelse (textarea, required)
     - Særlige vilkår (textarea, optional)
   - Notification: Email to assignee

4. Configure sequence flow condition:
   - Label: "Farlig race"
   - Condition: `${form.breed in ['Pitbull', 'American Staffordshire Terrier', 'Tosa Inu', 'Amerikansk Bulldog', 'Boerboel', 'Kangal', 'Centralasiatisk Ovtcharka', 'Kaukasisk Ovtcharka', 'Sydrussisk Ovtcharka', 'Tornjak', 'Sarplaninac']}`

**Step 3: Configure Regular Breed Path (0:50-1:10)**
1. Drag connection from Gateway directly to Payment Task
2. Configure sequence flow:
   - Label: "Normal race - automatisk godkendelse"
   - Condition: `else` (default path)
   - Visual: Checkmark icon on flow

**Step 4: Merge Paths (1:10-1:30)**
1. Connect User Task to Payment Task (merge point)
2. Add conditional check after User Task:
   - If approved → continue to Payment
   - If rejected → End Event with rejection message

3. Add second Gateway after User Task:
   - Type: Exclusive Gateway
   - Decision: ${userTask.approved}
   - Path 1 (approved): → Payment Task
   - Path 2 (rejected): → End Event "Ansøgning afvist"

#### VISUAL CALLOUTS
- Highlight Gateway diamond shape
- Show branching paths with different colors:
  - Red path: Manual approval required
  - Green path: Automatic approval
- Display condition expressions in monospace font
- Show merge point where paths rejoin
- Animate flow simulation:
  - Token moving through automatic path (green)
  - Token moving through manual path (red)
- Box the dangerous breed list
- Show "Default path" indicator on normal breed flow

#### RECORDING TIPS
- Explain gateways clearly: "Decision points in the workflow"
- Show the breed list condition slowly
- Emphasize the automation: "Automatic routing based on rules"
- Demonstrate both paths visually
- Technical but clear language
- Pause on completed conditional logic (5 seconds)

---

### [5:00-6:30] Using the Danish Municipal Palette (90 seconds)

#### NARRATION (Danish)
"Lad os se på nogle af de unikke danske kommunale task-typer. GIS Validation til at tjekke adresser og zonering. Neighbor Notification til automatisk naboorientering. Integration Tasks til SBSYS, BBR, og CPR-registret. Disse tasks er præ-konfigureret med danske standarder og integrationer."

#### NARRATION (English)
"Let's look at some of the unique Danish municipal task types. GIS Validation for checking addresses and zoning. Neighbor Notification for automatic neighbor notification. Integration Tasks for SBSYS, BBR, and CPR register. These tasks are pre-configured with Danish standards and integrations."

#### SCREEN ACTIONS

**GIS Validation Task (0:00-0:30)**
1. Drag "🗺️ GIS Validation" to demo canvas
2. Show properties panel:
   - Label: "Valider adresse og zonering"
   - Input address: ${mitid.address}
   - Validation checks:
     -  Address exists in DAR (Danish Address Register)
     -  Fetch zone type
     -  Calculate building coverage
     -  Check distance to boundaries
   - Output variables:
     - ${gis.zoneType}
     - ${gis.buildingCoverage}
     - ${gis.propertyId}
   - Integration: WMS/WFS endpoints (pre-configured)

**Neighbor Notification Task (0:30-1:00)**
1. Drag "📨 Neighbor Notification" to canvas
2. Show properties:
   - Label: "Send naboorientering"
   - Center address: ${form.propertyAddress}
   - Notification radius: 25 meters
   - Auto-identify neighbors: Yes (via GIS)
   - Notification method:
     -  Digital Post
     -  Email (if no Digital Post)
     - Physical letter (fallback)
   - Objection period: 14 days
   - Template: "neighbor_notification_building"
   - Track responses: Yes

**Integration Task - BBR (1:00-1:30)**
1. Drag "🔄 Integration Task" to canvas
2. Show properties:
   - Label: "Hent bygningsdata fra BBR"
   - Integration type: "BBR (Building and Dwelling Register)"
   - Method: "GET building data"
   - Input: Property ID (${form.propertyId})
   - Authentication: API key (configured in system)
   - Output variables:
     - ${bbr.buildingYear}
     - ${bbr.buildingArea}
     - ${bbr.buildingUse}
     - ${bbr.energyLabel}
   - Error handling: "Continue with manual input"
   - Timeout: 10 seconds

#### VISUAL CALLOUTS
- Highlight "Danish-specific" badge on these tasks
- Show pre-configured integration endpoints
- Display variable mapping visually
- Box the automatic neighbor identification feature
- Highlight BBR/CPR/SBSYS logos
- Show "No API coding needed" annotation
- Display dropdown of available integrations:
  - BBR (Building Register)
  - CPR (Civil Registration)
  - CVR (Business Register)
  - DAR (Address Register)
  - SBSYS (Case Management)
  - Datafordeleren (Data Distribution)
  - Kortforsyningen (Map Supply)

#### RECORDING TIPS
- Emphasize Danish-specific nature
- Show how integrations are pre-configured
- Explain variable mapping clearly
- Professional, impressed tone: "This is unique to Danish municipalities"
- Point out each integration option
- Show dropdown of integration types

---

### [6:30-7:30] Validation and Testing (60 seconds)

#### NARRATION (Danish)
"Før vi gemmer workflow'en, validerer systemet automatisk for fejl. Det tjekker, at alle tasks er forbundet korrekt, at alle required felter er udfyldt, og at der ikke er uendelige loops. Vi kan også teste workflow'en direkte i editoren med simulerede data."

#### NARRATION (English)
"Before we save the workflow, the system automatically validates for errors. It checks that all tasks are connected correctly, that all required fields are filled in, and that there are no infinite loops. We can also test the workflow directly in the editor with simulated data."

#### SCREEN ACTIONS

**Step 1: Automatic Validation (0:00-0:20)**
1. Click "Valider workflow" button
2. Show validation running (progress bar)
3. Display validation results panel:

   **Validation Results:**

    **Structure validation**: PASS
     - All tasks connected
     - Start event present
     - End event present
     - No orphaned tasks

    **Configuration validation**: PASS
     - All required properties set
     - Valid variable syntax
     - Correct data types

   ⚠ **Best practice warnings**: 2 warnings
     - Warning: "Email task has no error handler"
     - Suggestion: "Add error handling for email delivery failures"
     - Warning: "Consider adding timeout to payment task"

    **No errors detected - Workflow ready to deploy**

**Step 2: Simulation Mode (0:20-0:50)**
1. Click "Test workflow" button
2. Enter test data:
   - Dog name: "Fido"
   - Breed: "Labrador" (normal breed)
   - Birth date: "2023-05-15"
   - Chip number: "123456789012345"
3. Click "Run simulation"
4. Watch workflow execution:
   - Animated token moves through workflow
   - Each task highlights as it executes (green)
   - Variables shown in side panel
   - Execution path: Start → MitID → Form → Gateway → Payment (automatic path) → PDF → Email → End
   - Execution time: 2.3 seconds (simulated)
5. Show simulation results:
   -  Workflow completed successfully
   - Generated PDF: [preview thumbnail]
   - Email sent to: test@example.dk
   - Total steps: 7
   - Conditional branches taken: Automatic approval

**Step 3: Error Scenario Test (0:50-1:00)**
1. Run second simulation with dangerous breed:
   - Dog name: "Rex"
   - Breed: "Pitbull" (requires approval)
2. Watch execution:
   - Path: Start → MitID → Form → Gateway → User Task (manual) → [Wait for approval]
   - Status: Waiting for user input
   - Assigned to: "Natur & Miljø"

#### VISUAL CALLOUTS
- Highlight validation checkmarks (green)
- Box warning messages (yellow)
- Show animated token flow during simulation
- Display execution time
- Highlight conditional paths taken (different colors)
- Show variable values in real-time during simulation
- Display "Simulation Mode" badge
- Animate task completion sequence

#### RECORDING TIPS
- Explain validation types clearly
- Show the animated token flow - it's visual and impressive
- Emphasize automatic error checking
- Explain best practice warnings
- Show both success and waiting states
- Professional, thorough tone

---

### [7:30-8:30] Template Gallery and Workflow Sharing (60 seconds)

#### NARRATION (Danish)
"Når workflow'en er klar, kan vi gemme den i template-galleriet og dele den med andre kommuner. ÅbenForms har allerede 16 færdige templates, som du kan kopiere og tilpasse. Du kan også eksportere workflow'en som BPMN XML-fil og importere workflows fra andre systemer."

#### NARRATION (English)
"When the workflow is ready, we can save it to the template gallery and share it with other municipalities. ÅbenForms already has 16 ready-made templates that you can copy and customize. You can also export the workflow as BPMN XML file and import workflows from other systems."

#### SCREEN ACTIONS

**Step 1: Save to Template Gallery (0:00-0:20)**
1. Click "Gem workflow" button
2. Save dialog appears:
   - Workflow navn: "Hunderegistrering"
   - Version: 1.0.0
   - Kategori: "Natur & Miljø"
   - Synlighed:
     - ◉ Privat (kun din kommune)
     - ○ Offentlig (deling med andre kommuner)
     - ○ Community template (alle kan bruge)
   - Tags: "hund, registrering, betaling, simpel"
   - Beskrivelse: "Registrering af ny hund med automatisk eller manuel godkendelse baseret på race"
3. Click "Gem"
4. Success message: "Workflow gemt i galleriet "

**Step 2: Template Gallery Browse (0:20-0:40)**
1. Return to Template Gallery
2. Show workflow cards:

   **Simpel (1-5 tasks):**
   - Hunderegistrering (just created)
   - Affald - Bestil ekstra tømning
   - Flytning - Folkeregister opdatering

   **Mellem (6-10 tasks):**
   - Parkeringslicens
   - Vielse
   - Pas og kørekort - Fornyelse

   **Kompleks (11+ tasks):**
   - Byggetilladelse
   - Fødevarevirksomhed - Registrering
   - Miljøgodkendelse

3. Click on "Parkeringslicens" template
4. Show "Use this template" button
5. Show "Tilpas til din kommune" option

**Step 3: Export/Import (0:40-1:00)**
1. Click "Eksporter" on Hunderegistrering workflow
2. Show export options:
   -  BPMN 2.0 XML (standard format)
   -  JSON (ÅbenForms format with config)
   -  SVG (visual diagram)
   -  PNG (image)
3. Click "Download BPMN XML"
4. File downloads: `hunderegistrering-v1.0.0.bpmn`
5. Show brief XML preview in text editor:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<bpmn:definitions xmlns:bpmn="http://www.omg.org/spec/BPMN/20100524/MODEL"
                  xmlns:aabenforms="http://aabenforms.dk/bpmn/extensions">
  <bpmn:process id="hunderegistrering" name="Hunderegistrering">
    <bpmn:startEvent id="start" name="Borger starter registrering"/>
    <bpmn:task id="mitid" name="Log ind med MitID"
               aabenforms:type="mitid-authentication">
      <aabenforms:assuranceLevel>substantial</aabenforms:assuranceLevel>
    </bpmn:task>
    ...
  </bpmn:process>
</bpmn:definitions>
```

6. Show "Importer workflow" button (reverse process)

#### VISUAL CALLOUTS
- Highlight "Community Sharing" feature
- Show template count: "16 færdige templates"
- Display complexity indicators on each template
- Highlight export format options
- Show BPMN logo (industry standard)
- Box the XML code preview
- Display "Import/Export" workflow diagram
- Show version control indicator: "v1.0.0"

#### RECORDING TIPS
- Emphasize community sharing aspect
- Show template gallery variety
- Explain BPMN standard interoperability
- Show XML briefly but don't dwell on it
- Professional, collaborative tone
- Highlight reusability

---

### [8:30-9:30] Advanced Features (60 seconds)

#### NARRATION (Danish)
"Lad os kort se på nogle avancerede features. Subprocesses lader dig genbruge workflow-dele. Timers kan automatisk eskalere sager efter deadlines. Event-baserede gateways kan vente på eksterne hændelser. Og parallel gateways kan udføre flere opgaver samtidigt for at spare tid."

#### NARRATION (English)
"Let's briefly look at some advanced features. Subprocesses let you reuse workflow parts. Timers can automatically escalate cases after deadlines. Event-based gateways can wait for external events. And parallel gateways can execute multiple tasks simultaneously to save time."

#### SCREEN ACTIONS

**Feature 1: Subprocess (0:00-0:15)**
1. Drag " Subprocess" to canvas
2. Show properties:
   - Label: "Standard MitID + Betaling"
   - Reusable subprocess: Yes
   - Contains: MitID Auth + Payment + Receipt
   - Used in: 8 other workflows
3. Double-click to expand subprocess
4. Show internal tasks
5. Click back to parent workflow

**Feature 2: Timer Event (0:15-0:30)**
1. Add timer to User Task (manual approval)
2. Configure:
   - Type: "Boundary Timer"
   - Duration: 3 work days
   - Action on timeout: "Escalate to manager"
   - Notification: Email to manager
3. Show timer icon on task boundary

**Feature 3: Parallel Gateway (0:30-0:45)**
1. Add "Parallel Gateway (AND)" after Form Task
2. Split into 3 parallel paths:
   - Path 1: Generate PDF
   - Path 2: Send Email
   - Path 3: Update Database
3. Add join gateway after parallel tasks
4. Show all paths must complete before continuing
5. Demonstrate with simulation:
   - All 3 tasks execute simultaneously
   - Wait for all to complete
   - Then continue to next step

**Feature 4: Event-Based Gateway (0:45-1:00)**
1. Show example with building permit:
   - Wait for either:
     - Neighbor approval (message event)
     - 14-day timeout (timer event)
   - Whichever comes first triggers next step
2. Show event-based gateway icon (circle with multiple exits)

#### VISUAL CALLOUTS
- Highlight subprocess icon (folder icon)
- Show timer icon (clock)
- Display parallel gateway (diamond with + sign)
- Show event-based gateway (diamond with pentagon inside)
- Animate parallel execution (3 tokens moving simultaneously)
- Display "Advanced Features" badge
- Show reusability count: "Används i 8 workflows"

#### RECORDING TIPS
- Quick overview - don't go too deep
- Show each feature briefly
- Emphasize power and flexibility
- Impressive, technical tone
- Focus on business value: "Save time with parallel processing"

---

### [9:30-10:00] Summary & Call to Action (30 seconds)

#### NARRATION (Danish)
"Og det var det! Du har nu set, hvordan du kan bygge komplekse workflows uden kodning, bruge danske kommunale task-typer, validere og teste workflows, og dele templates med andre kommuner. Med ÅbenForms Visual Workflow Builder kan selv ikke-tekniske medarbejdere oprette selvbetjeningsløsninger. Start med vores 16 templates, tilpas dem til din kommune, og del dine løsninger med fællesskabet. God arbejdslyst!"

#### NARRATION (English)
"And that's it! You've now seen how to build complex workflows without coding, use Danish municipal task types, validate and test workflows, and share templates with other municipalities. With the ÅbenForms Visual Workflow Builder, even non-technical staff can create self-service solutions. Start with our 16 templates, customize them for your municipality, and share your solutions with the community. Happy workflow building!"

#### SCREEN ACTIONS
1. Show completed workflow one more time (zoom out to see full flow)
2. Transition to summary slide showing:
   - **Key Takeaways:**
     -  No coding required
     -  16 Danish municipal task types
     -  BPMN 2.0 industry standard
     -  Automatic validation
     -  Simulation and testing
     -  Template gallery
     -  Community sharing
     -  Import/export workflows

3. Show call-to-action:
   - "Start building workflows today!"
   - "Try demo: demo.aabenforms.dk/workflow-builder"
   - "Download templates: github.com/aabenforms/templates"
   - "Join community: community.aabenforms.dk"

4. Fade to end screen with ÅbenForms logo

#### VISUAL CALLOUTS
- Show full workflow in final view
- Highlight key features in summary list
- Display QR codes for demo and templates
- Show "Get Started" button
- Display community stats:
  - 23 contributors
  - 16 ready templates
  - 12 municipalities using
- Animate checklist items

#### RECORDING TIPS
- Confident, empowering closing tone
- Review key benefits
- Clear call-to-action
- Encouraging: "You can do this!"
- Hold end screen for 5 seconds

---

## POST-PRODUCTION NOTES

### Editing Checklist
- [ ] Add intro title: "Visual Workflow Builder Tutorial"
- [ ] Add panel labels overlay (Left/Center/Right)
- [ ] Highlight cursor movements (enlarge cursor, add click effects)
- [ ] Add zoom effects on properties panels
- [ ] Insert task palette legend overlay
- [ ] Animate workflow token flow in simulation
- [ ] Add code syntax highlighting on XML preview
- [ ] Insert transition effects between sections
- [ ] Add background music (educational, subtle)
- [ ] Balance audio levels
- [ ] Add Danish subtitles
- [ ] Add English subtitles (separate version)
- [ ] Add chapter markers for YouTube
- [ ] Create thumbnail with workflow diagram

### Graphics to Create
1. BPMN.io editor interface diagram with labels
2. Task palette with all 16 icons and names
3. Workflow examples (simple, medium, complex)
4. Variable syntax guide (${} notation)
5. Gateway types comparison chart
6. Validation results panel mockup
7. Simulation token animation
8. Template gallery grid
9. Import/export flow diagram
10. Advanced features overview

### Screen Captures Needed
- BPMN.io editor (all three panels)
- Complete workflow being built step by step
- Properties panel for each task type
- Gateway configuration
- Validation results
- Simulation with token animation
- Template gallery browse
- Export dialog and BPMN XML
- Advanced features demos

### Annotations to Add
- Panel labels (Palette, Canvas, Properties)
- Task type labels
- Variable syntax highlighting
- Connection flow arrows
- Gateway type indicators
- Validation status icons
- Simulation path highlighting
- Complexity indicators on templates

---

## DANISH MUNICIPAL TASK PALETTE (16 Types)

Complete reference for demonstration:

1. **📋 Start Event** - Workflow start trigger
2. **🔐 MitID Authentication** - Citizen authentication
3. ** Form Task** - Data collection forms
4. ** Validation Task** - Data validation logic
5. **💳 Payment Task** - Nets payment processing
6. **📧 Email Task** - Email notifications
7. **📱 SMS Task** - SMS notifications
8. **📄 PDF Generation** - Document generation
9. **🗺️ GIS Validation** - Address/zoning validation
10. **📨 Neighbor Notification** - Automatic neighbor notification
11. **🔄 Integration Task** - BBR/CPR/SBSYS integration
12. **👤 User Task** - Manual staff task
13. **⚙️ Service Task** - Automated background task
14. **❓ Gateway** - Decision point (XOR/AND/OR/Event)
15. ** Subprocess** - Reusable workflow component
16. **🏁 End Event** - Workflow completion

---

## WORKFLOW EXAMPLES TO DEMONSTRATE

### Simple Workflow (1-5 tasks)
- Dog Registration (demonstrated in video)
- Extra waste collection request
- Civil registration address update

### Medium Workflow (6-10 tasks)
- Parking permit (5 minute demo video)
- Marriage booking (5 minute demo video)
- Passport renewal

### Complex Workflow (11+ tasks)
- Building permit (7 minute demo video)
- Food establishment registration
- Environmental permit

---

## TECHNICAL TALKING POINTS

### For IT Managers
- "BPMN 2.0 standard ensures interoperability"
- "Export workflows and import into other BPMN engines"
- "Version control built-in"
- "REST API for workflow deployment"
- "Camunda-compatible execution engine"

### For Workflow Designers
- "No programming knowledge required"
- "Visual drag-and-drop interface"
- "Real-time validation and testing"
- "16 pre-configured Danish task types"
- "Reusable subprocess components"

### For Municipal Staff
- "Create new services without IT department"
- "Copy and customize existing templates"
- "Test workflows before deployment"
- "Share solutions with other municipalities"
- "Community-driven template library"

---

## ALTERNATIVE SECTIONS

### Extended Version (15 minutes)
Add sections on:
- Advanced data mapping
- Complex error handling
- Multi-language forms
- Integration testing
- Performance optimization
- Workflow versioning and rollback

### Technical Deep-Dive (20 minutes)
Include:
- BPMN XML structure explanation
- Custom task development
- API integration configuration
- Database schema for workflows
- Execution engine internals

---

## DELIVERY FORMATS

### YouTube Version
- Resolution: 1920x1080, 30fps
- Chapters:
  - 0:00 Introduction
  - 0:30 Editor Overview
  - 1:30 Building Simple Workflow
  - 3:30 Conditional Logic
  - 5:00 Danish Municipal Tasks
  - 6:30 Validation & Testing
  - 7:30 Template Gallery
  - 8:30 Advanced Features
  - 9:30 Summary

### Training Version
- Extended to 15 minutes
- Include exercises for viewers
- Downloadable workflow templates
- Quiz questions at end

### Conference Demo
- 20-minute live demonstration
- Include Q&A time
- Show backend admin interface
- Demonstrate deployment process

### Social Media Teaser (45 seconds)
- Show workflow being built in fast motion
- Highlight "no coding" message
- Display finished workflow
- End with "Try it yourself" CTA

---

## SCRIPT APPROVAL

- [ ] Reviewed by: _______________
- [ ] Technical accuracy verified: _______________
- [ ] BPMN.io features confirmed: _______________
- [ ] Danish task palette validated: _______________
- [ ] Template gallery prepared: _______________
- [ ] Demo environment tested: _______________
- [ ] Danish translation reviewed: _______________
- [ ] Ready for production: _______________

**Date**: _______________
**Approved by**: _______________
