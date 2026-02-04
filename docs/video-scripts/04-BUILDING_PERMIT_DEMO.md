# Video Script 04: Building Permit Application Demo (7 minutes)

## Video Metadata
- **Duration**: 7 minutes
- **Target Audience**: Municipal planners, building inspectors, architects, property owners
- **Goal**: Showcase advanced features (GIS validation, multi-stage workflow, SBSYS integration)
- **Tone**: Technical, authoritative, innovative

---

## Technical Setup

### Recording Environment
- **Browser**: Chrome with browser extensions disabled
- **Resolution**: 1920x1080 (important for GIS map clarity)
- **Screen recording**: 30fps, high quality for map rendering
- **Test environment**: https://demo.aabenforms.dk/byggesag

### Demo Account Credentials

**Applicant**:
- CPR: 0101701234
- Name: Mette Mortensen
- Address: Skovvej 15, 8000 Aarhus C
- Property ID: 751-12345 (test property)
- Email: mette.mortensen@example.dk
- Phone: +45 40 12 34 56

**Architect/Agent** (optional):
- CVR: 12345678
- Company: Arkitekt A/S
- Contact: Peter Pedersen
- Email: pp@arkitekt.dk

### Pre-recording Checklist
- [ ] GIS server running with test data
- [ ] Load test property (751-12345) with zoning: Residential
- [ ] Prepare neighbor data (3 adjacent properties)
- [ ] Set up SBSYS integration test endpoint
- [ ] Prepare building plans PDF (sample architectural drawings)
- [ ] Configure automatic neighbor notification emails
- [ ] Set up multi-stage approval workflow (3 stages)
- [ ] Test document upload (max 10MB files)

### Visual Assets Needed
- GIS zoning map with color-coded zones
- Sample architectural drawings (PDF, 2-5 pages)
- Property cadastral map
- Neighbor notification email template
- SBSYS integration flow diagram
- Multi-stage approval workflow diagram
- GDPR compliance badges

---

## SCRIPT

### [0:00-0:25] Introduction (25 seconds)

#### NARRATION (Danish)
"Velkommen til demonstration af byggesagsbehandling i ÅbenForms. Dette er vores mest avancerede workflow, der viser funktioner som går ud over, hvad XFlow kan tilbyde: GIS-zonevalidering, automatisk naboorientering, integration med SBSYS, og en intelligent multi-stage godkendelsesproces. Lad os se, hvordan en borger kan indsende en byggeansøgning."

#### NARRATION (English)
"Welcome to the building permit application demonstration in ÅbenForms. This is our most advanced workflow, showcasing features that go beyond what XFlow can offer: GIS zoning validation, automatic neighbor notification, SBSYS integration, and an intelligent multi-stage approval process. Let's see how a citizen can submit a building application."

#### SCREEN ACTIONS
1. Start at ÅbenForms homepage
2. Navigate to "Byg & Miljø" → "Byggetilladelse"
3. Show landing page with:
   - Building permit categories (extension, renovation, new construction)
   - Process timeline visualization (7-14 days)
   - Requirements checklist
   - Interactive map showing jurisdiction area

#### VISUAL CALLOUTS
- Highlight "Byggetilladelse" menu item
- Show complexity indicator: "Kompleks" (Complex)
- Display estimated time: "20-30 minutter for ansøgning"
- Show processing time: "7-14 dage sagsbehandling"
- Badge: "GIS-integration" (unique feature)
- Badge: "SBSYS-integration" (unique feature)

#### RECORDING TIPS
- Professional, confident tone
- Emphasize advanced features
- Pause on feature badges
- Technical but accessible language

---

### [0:25-1:00] Project Type Selection (35 seconds)

#### NARRATION (Danish)
"Først vælger borgeren projekttype. I dette eksempel ansøger Mette Mortensen om tilladelse til at bygge en tilbygning til sit eksisterende hus på Skovvej 15. Systemet viser straks, hvilke dokumenter der kræves baseret på projekttypen."

#### NARRATION (English)
"First, the citizen selects the project type. In this example, Mette Mortensen is applying for permission to build an extension to her existing house at Skovvej 15. The system immediately shows which documents are required based on the project type."

#### SCREEN ACTIONS
1. Show "Vælg projekttype" page with options:

   **Tilbygning** (Selected)
   - Udvid eksisterende bygning
   - Kræver: Tegninger, beregninger, naboorientering
   - Sagsbehandlingstid: 7-14 dage

   **Nybyggeri**
   - Nyt fritliggende byggeri
   - Kræver: Komplette arkitekttegninger, fundamentsberegninger

   **Ombygning**
   - Ændring af eksisterende bygning
   - Kræver: Tegninger, statiske beregninger

   **Fritliggende udhus/carport**
   - Mindre byggeri (<50 m²)
   - Kræver: Placeringstegning

2. Click "Tilbygning"
3. Show expanded requirements:
   -  Situationsplan (1:500)
   -  Plantegninger (1:100)
   -  Facadetegninger (1:100)
   -  Snittegninger (1:100)
   -  BBR-udskrift
   -  Energiberegninger (BR18)
4. Click "Start ansøgning"

#### VISUAL CALLOUTS
- Highlight "Tilbygning" card with border
- Show document checklist with icons
- Display estimated processing time
- Animate requirement list expanding
- Show file format requirements: PDF, DWG, eller billedfiler

#### RECORDING TIPS
- Explain each project type briefly
- Emphasize dynamic requirements (changes based on selection)
- Click through project types to show differences
- Professional, instructional tone

---

### [1:00-1:45] MitID Login & Property Identification (45 seconds)

#### NARRATION (Danish)
"Nu logger Mette ind med MitID. Systemet henter automatisk hendes ejendomsoplysninger fra BBR-registret og viser dem på et interaktivt kort. Dette er hvor GIS-integrationen kommer ind - vi kan se præcis, hvor ejendommen ligger, og hvilken zonetype den er i."

#### NARRATION (English)
"Now Mette logs in with MitID. The system automatically retrieves her property information from the BBR register and displays it on an interactive map. This is where the GIS integration comes in - we can see exactly where the property is located and what zone type it's in."

#### SCREEN ACTIONS
1. MitID login flow:
   - Enter CPR: 0101701234
   - Approve in MitID app
   - Return to ÅbenForms
2. Show retrieved data:
   - Navn: Mette Mortensen
   - Adresse: Skovvej 15, 8000 Aarhus C
   - Ejendomsnr: 751-12345
3. Show **GIS Map Interface** (KEY FEATURE):
   - Interactive map centered on Skovvej 15
   - Property boundaries highlighted in blue
   - Neighboring properties outlined
   - Zoning overlay (color-coded):
     - Property zone: Residential (green)
     - Surrounding: Mixed residential/commercial
   - Map controls: Zoom, pan, measure tool
4. Click "Bekræft ejendom"

#### VISUAL CALLOUTS
- Highlight property on map with pulsing marker
- Show property boundary polygon (blue outline)
- Display zone information tooltip:
  - "Zone: Bolig - lav"
  - "Max. bebyggelsesprocent: 30%"
  - "Max. højde: 8.5 m"
- Overlay zoning legend:
  - Green: Residential
  - Yellow: Mixed-use
  - Red: Commercial
  - Blue: Public/Institutional
- Show neighboring properties (numbered 1-3)
- Display cadastral number: 751-12345

#### RECORDING TIPS
- **THIS IS A KEY DIFFERENTIATOR** - emphasize heavily
- Zoom in on map to show property clearly
- Pan around to show context
- Explain zoning rules briefly
- Professional, impressive tone: "Notice the automatic GIS integration"
- Pause on map for 5 seconds minimum

---

### [1:45-3:00] GIS Zoning Validation & Automatic Checks (75 seconds)

#### NARRATION (Danish)
"Her sker magien. Baseret på ejendommens zonering kører systemet automatiske valideringer. Det tjekker byggelovgivning, lokalplan, og kommuneplan. For Mettes tilbygning på 25 kvadratmeter tjekkes: maksimal bebyggelsesprocent, afstand til skel, højdebegrænsninger, og parkerings krav. Alt dette sker automatisk via GIS-integration - en funktion XFlow ikke har."

#### NARRATION (English)
"Here's where the magic happens. Based on the property's zoning, the system runs automatic validations. It checks building regulations, local plan, and municipal plan. For Mette's 25 square meter extension, it checks: maximum building coverage, distance to boundaries, height restrictions, and parking requirements. All this happens automatically via GIS integration - a feature XFlow doesn't have."

#### SCREEN ACTIONS
1. Show "Automatisk validering" progress screen:
   - Loading animation
   - Progress indicators:
     -  Henter ejendomsdata fra BBR
     -  Analyserer zonering
     -  Tjekker lokalplan nr. 451
     -  Validerer byggelovgivning
     - ⏳ Beregner bebyggelsesprocent
     - ⏳ Tjekker afstand til skel

2. Show validation results panel:

   **Zoneoplysninger:**
   - Zone: Bolig - lav (§5.1.2)
   - Lokalplan: Nr. 451 "Skovkvarteret"
   - Gældende fra: 2018

   **Automatiske tjek - GODKENDT **

    **Bebyggelsesprocent**:
     - Nuværende: 22%
     - Med tilbygning: 28%
     - Maksimum: 30%
     - Status: OK (2% margin)

    **Afstand til skel**:
     - Nord: 5.2 m (krav: 2.5 m) 
     - Syd: 3.1 m (krav: 2.5 m) 
     - Øst: 7.5 m (krav: 2.5 m) 
     - Vest: 12.0 m (krav: 2.5 m) 

    **Højde**:
     - Planlagt: 6.2 m
     - Maksimum: 8.5 m 

    **Parkering**:
     - Eksisterende: 2 pladser
     - Påkrævet: 2 pladser 
     - Ingen ændring nødvendig

   ⚠ **Bemærkning**:
   - Tilbygningen kræver naboorientering (indenfor 25m)

3. Show interactive map update:
   - Proposed extension outlined in dashed blue line
   - Distance measurements shown with arrows
   - Building coverage percentage visualized (heat map)
   - Neighbor notification zone (25m radius circle)

#### VISUAL CALLOUTS
- Animate progress checkmarks appearing one by one
- Highlight each validation result with color coding:
  - Green : Approved
  - Red ✗: Rejected
  - Yellow ⚠: Warning/Note
- Show building coverage calculation:
  - Existing: 165 m² / 750 m² = 22%
  - Addition: +25 m²
  - New total: 190 m² / 750 m² = 25.3%
- Display distance measurements on map with arrows and dimensions
- Circle the warning about neighbor notification
- Box the GIS validation badge: "Automatisk GIS-validering "

#### RECORDING TIPS
- **CRITICAL SECTION** - this is the main differentiator
- Speak slowly and clearly through each validation
- Emphasize "automatic" repeatedly
- Pause on each checkmark result
- Explain the business value: "No manual checking required"
- Technical but impressed tone: "Watch this automatic validation"
- Hold on map visualization for 5 seconds

---

### [3:00-4:00] Document Upload & Project Details (60 seconds)

#### NARRATION (Danish)
"Nu uploader Mette de påkrævede dokumenter. Systemet validerer filstørrelser, formater, og tjekker endda, at tegningerne er i den rigtige målestok. Hun beskriver projektet og angiver byggeriet skal udføres af en autoriseret entreprenør."

#### NARRATION (English)
"Now Mette uploads the required documents. The system validates file sizes, formats, and even checks that the drawings are at the correct scale. She describes the project and indicates the construction will be performed by an authorized contractor."

#### SCREEN ACTIONS
1. Show "Trin 2 - Upload dokumenter"
2. Document upload interface with sections:

   **Påkrævet:**
   - Situationsplan (1:500): [Upload] → Upload: situationsplan.pdf (1.2 MB) 
   - Plantegninger (1:100): [Upload] → Upload: plantegninger.pdf (2.5 MB) 
   - Facadetegninger (1:100): [Upload] → Upload: facade.pdf (1.8 MB) 
   - Snittegninger (1:100): [Upload] → Upload: snit.pdf (1.1 MB) 

   **Valgfrit:**
   - Konstruktionsberegninger: [Upload] → Upload: beregninger.pdf (0.8 MB) 
   - Energiberegninger: [Upload]
   - Visualiseringer: [Upload] → Upload: 3d-visualisering.jpg (4.2 MB) 

3. Show file validation:
   - File format check: PDF 
   - File size check: < 10 MB 
   - Drawing scale validation (OCR): 1:100 detected 
   - Virus scan: Clean 

4. Fill in project description:
   **Projektbeskrivelse:**
   "Tilbygning af 25 m² boligareal i ét plan. Tilbygningen placeres på husets sydside og indrettes som nyt køkken-alrum. Udført i gule mursten matchende eksisterende bygning. Tag: Røde teglsten, hældning 30°."

   **Entreprise:**
   - ◉ Autoriseret byggevirksomhed
   - CVR: 98765432
   - Virksomhed: Byg & Anlæg ApS

   **Forventet byggeperiode:**
   - Start: 15-04-2024
   - Slut: 30-06-2024
   - Varighed: 11 uger

5. Click "Næste"

#### VISUAL CALLOUTS
- Show upload progress bars for each file
- Display file validation checkmarks
- Highlight scale detection: "1:100 detected fra tegning "
- Show thumbnail preview of uploaded drawings
- Box the contractor information section
- Display timeline visualization for construction period
- Show character counter on description field (157/2000)

#### RECORDING TIPS
- Explain document validation features
- Show actual PDF previews briefly
- Emphasize automatic validation
- Professional, thorough tone
- Type project description at moderate speed

---

### [4:00-4:45] Automatic Neighbor Notification (45 seconds)

#### NARRATION (Danish)
"Nu sker noget unikt. Baseret på GIS-analysen har systemet identificeret tre naboer indenfor 25 meter, som skal orienteres om byggeriet. ÅbenForms sender automatisk breve til alle naboer med projektbeskrivelse og mulighed for at komme med indsigelser. Dette er fuldt automatiseret - ingen manuel sagsbehandling nødvendig."

#### NARRATION (English)
"Now something unique happens. Based on the GIS analysis, the system has identified three neighbors within 25 meters who must be notified about the construction. ÅbenForms automatically sends letters to all neighbors with project description and opportunity to raise objections. This is fully automated - no manual case processing required."

#### SCREEN ACTIONS
1. Show "Trin 3 - Naboorientering"
2. Display GIS-generated neighbor list:

   **Identificerede naboer (3):**

   **Nabo 1:**
   - Adresse: Skovvej 13
   - Ejendom: 751-12344
   - Afstand: 8.2 m (vest)
   - Ejer: Jens Nielsen
   - Status: Automatisk brev sendt 02-02-2024 

   **Nabo 2:**
   - Adresse: Skovvej 17
   - Ejendom: 751-12346
   - Afstand: 7.9 m (øst)
   - Ejer: Karen Larsen
   - Status: Automatisk brev sendt 02-02-2024 

   **Nabo 3:**
   - Adresse: Parkvej 22
   - Ejendom: 751-12401
   - Afstand: 22.5 m (syd)
   - Ejer: Peter Petersen
   - Status: Automatisk brev sendt 02-02-2024 

3. Show interactive map with:
   - Applicant property (blue)
   - 25m notification radius (dashed circle)
   - Neighbor properties highlighted (yellow)
   - Distance lines to each neighbor

4. Click "Vis brev-skabelon" to show auto-generated letter:
   - Header: Aarhus Kommune logo
   - Title: "Orientering om byggearbejde"
   - Property details
   - Project description (from form)
   - Objection period: 14 dage
   - Contact information

5. Checkboxes:
   -  Send brev med Digital Post
   -  Send kopi til min email
   -  Giv mig besked når naboer har set brevet

6. Click "Send naboorientering"

#### VISUAL CALLOUTS
- Highlight GIS automation: "Automatisk identificeret via GIS "
- Show distance calculations on map
- Animate notification radius circle
- Display neighbor properties highlighted in yellow
- Show letter template preview
- Highlight Digital Post logo (official communication)
- Display tracking feature: "Spor hvornår naboer åbner brevet"

#### RECORDING TIPS
- **ANOTHER KEY DIFFERENTIATOR** - emphasize strongly
- Explain the GIS calculation: "System calculated distances automatically"
- Show the map clearly with neighbor locations
- Pause on letter template
- Explain Danish legal requirement (14-day objection period)
- Impressed tone: "Completely automated neighbor notification"

---

### [4:45-5:30] Multi-Stage Approval Workflow (45 seconds)

#### NARRATION (Danish)
"Efter indsendelse går ansøgningen gennem en intelligent multi-stage workflow. Først validerer en AI-assistent tekniske krav. Derefter fordeles sagen automatisk til den rette sagsbehandler baseret på arbejdsbyrde og kompetencer. Til sidst godkendes af bygningsinspektør. Alt dette orkestreres af ÅbenForms BPMN-workflow engine."

#### NARRATION (English)
"After submission, the application goes through an intelligent multi-stage workflow. First, an AI assistant validates technical requirements. Then the case is automatically distributed to the right caseworker based on workload and competencies. Finally, it's approved by a building inspector. All this is orchestrated by the ÅbenForms BPMN workflow engine."

#### SCREEN ACTIONS
1. Show "Ansøgning indsendt " confirmation
2. Display workflow visualization:

   **Stage 1: Automatisk validering** (Completed )
   - AI-validering af dokumenter
   - Tjek af bebyggelsesprocent
   - Zonevalidering
   - Status: Godkendt automatisk
   - Tid: 2 sekunder

   **Stage 2: Naboorientering** (In Progress ⏳)
   - Breve sendt til 3 naboer
   - Indsigelsesperiode: 14 dage
   - Udløb: 16-02-2024
   - Status: Afventer svar

   **Stage 3: Sagsbehandler-vurdering** (Pending ⏸)
   - Tildelt: Vent på naboorientering
   - Estimeret start: 17-02-2024
   - Ansvarlig: Auto-tildeles baseret på kompetence

   **Stage 4: Bygningsinspektør godkendelse** (Pending ⏸)
   - Afventer stage 3
   - Estimeret: 20-02-2024

   **Stage 5: Tilladelse udstedt** (Pending ⏸)
   - SBSYS integration
   - PDF-generering
   - Digital Post til ansøger

3. Show workflow diagram (BPMN visualization):
   - Flow chart showing decision points
   - Parallel tasks (neighbor notification + document review)
   - Conditional logic (if objections → additional review)
   - Integration points (SBSYS, Digital Post)

4. Show tracking page:
   - Sagsnr: BYG-2024-001234
   - Status: Naboorientering (Stage 2)
   - Forventet sagsbehandling: 10-12 dage
   - Næste handling: Afvent naboorientering (14 dage)

#### VISUAL CALLOUTS
- Animate workflow stages with progress indicators
- Highlight completed stages (green checkmarks)
- Show current stage (blue, pulsing)
- Display future stages (gray, locked)
- Box the BPMN workflow diagram
- Highlight conditional logic nodes (diamond shapes)
- Show integration icons (SBSYS, Digital Post logos)
- Display estimated timeline on calendar visualization

#### RECORDING TIPS
- Technical, impressive tone
- Explain workflow stages clearly
- Emphasize automation and intelligence
- Point to BPMN diagram elements
- Explain parallel processing briefly
- Professional, confident delivery

---

### [5:30-6:15] SBSYS Integration & Data Sovereignty (45 seconds)

#### NARRATION (Danish)
"Når tilladelsen godkendes, integrerer ÅbenForms automatisk med kommunens SBSYS sagsbehandlingssystem. Alle data synkroniseres, men vigtigst af alt: alle personoplysninger håndteres efter GDPR. Systemet logger automatisk alle dataadgange, anonymiserer data efter lovkrav, og giver borgeren fuld transparens over, hvem der har set deres oplysninger."

#### NARRATION (English)
"When the permit is approved, ÅbenForms automatically integrates with the municipality's SBSYS case management system. All data is synchronized, but most importantly: all personal data is handled according to GDPR. The system automatically logs all data access, anonymizes data according to legal requirements, and gives citizens full transparency over who has viewed their information."

#### SCREEN ACTIONS
1. Show "Integration" panel:

   **SBSYS Integration:**
   - Status: Konfigureret 
   - Sagstype: Byggesag
   - Auto-oprettelse: Aktiveret
   - Datasynkronisering: Real-time
   - Sidste sync: 02-02-2024 10:34

2. Show GDPR compliance dashboard:

   **GDPR Compliance:**

    **Databehandler-aftale**
   - Status: Aktiv
   - Gyldig til: 31-12-2025

    **Adgangslog**
   - Alle åbninger logges
   - Opbevaring: 5 år
   - Borger-adgang: Via Min Side

    **Dataminimering**
   - Kun nødvendige felter indsamles
   - Automatisk sletning efter 10 år

    **Anonymisering**
   - Statistikdata anonymiseres automatisk
   - CPR-nummer krypteret i database

3. Show citizen access log page:
   **Hvem har set mine oplysninger:**

   | Dato | Tid | Bruger | Rolle | Formål |
   |------|-----|--------|-------|--------|
   | 02-02-2024 | 10:35 | System | Auto | Modtagelse |
   | 02-02-2024 | 10:35 | AI-assistent | Auto | Validering |
   | 02-02-2024 | 10:36 | Nabosystem | Auto | Adresseslag |
   | - | - | - | - | Afventer sagsbehandler |

4. Show data export options:
   - Download alle mine data (GDPR Art. 15)
   - Anmod om sletning (GDPR Art. 17)
   - Gør indsigelse (GDPR Art. 21)

#### VISUAL CALLOUTS
- Highlight SBSYS logo with connection icon
- Show real-time sync indicator (green pulse)
- Display GDPR compliance badges prominently:
  - GDPR Compliant 
  - Data Protection Impact Assessment (DPIA) 
  - EU Standard Contractual Clauses 
- Highlight access log with eye icon
- Box data minimization principle
- Show encryption badge on sensitive data
- Display "Citizen Rights" panel prominently

#### RECORDING TIPS
- Serious, trustworthy tone for GDPR section
- Emphasize data sovereignty and transparency
- Explain access logging clearly
- Professional, compliant tone
- This builds trust with municipalities

---

### [6:15-7:00] Approval & Permit Issuance (45 seconds)

#### NARRATION (Danish)
"Efter naboorienterings-perioden og sagsbehandler-godkendelse udstedes tilladelsen automatisk. Borgeren modtager byggetilladelsen via Digital Post, med alle vilkår, frister, og tegninger. Hun kan også downloade en byggetilladelsesplakat til montering på byggepladsen. Hele processen fra ansøgning til tilladelse: 12 dage. Sammenlignet med manuelle processer på 4-8 uger, er det en reduktion på 75%."

#### NARRATION (English)
"After the neighbor notification period and caseworker approval, the permit is automatically issued. The citizen receives the building permit via Digital Post, with all conditions, deadlines, and drawings. She can also download a building permit sign for mounting at the construction site. The entire process from application to permit: 12 days. Compared to manual processes of 4-8 weeks, that's a 75% reduction."

#### SCREEN ACTIONS
1. Show approval notification:
   " Din byggetilladelse er godkendt!"

2. Display permit details:
   - Sagsnr: BYG-2024-001234
   - Tilladelsesnr: BT-2024-AH-5678
   - Udstedt: 14-02-2024
   - Gyldig til: 14-02-2027 (3 år)
   - Projekt: Tilbygning 25 m², Skovvej 15

3. Show action buttons:
   - **Download byggetilladelse (PDF)**
   - Download byggetilladelsesplakat (A3 PDF)
   - Se alle dokumenter
   - Anmeld byggestart (når I går i gang)
   - Book byggesagsmøde (valgfrit)

4. Open permit PDF showing:
   - Header: Aarhus Kommune logo + "Byggetilladelse"
   - Permit number and validity
   - Property information
   - Approved drawings (thumbnails)
   - Conditions:
     - Byggestart skal anmeldes senest 14 dage før
     - Færdigmelding skal indsendes
     - Tilladelsesplakat skal opsættes synligt
   - Signature: Digital signatur fra bygningsinspektør

5. Show construction site sign (A3 poster):
   - Large heading: "BYGGETILLADELSE"
   - Permit number: BT-2024-AH-5678
   - Property: Skovvej 15
   - QR code (links to permit details)

6. Show timeline comparison graphic:
   - Manual process: 4-8 uger 📅
   - ÅbenForms: 12 dage ⚡
   - Time saved: 75%

#### VISUAL CALLOUTS
- Animate approval checkmark (large, celebratory)
- Highlight permit number prominently
- Show validity period (3 years) with calendar icon
- Display PDF permit with official seal
- Show construction sign clearly
- Highlight QR code on sign
- Display time comparison chart with bold numbers
- Show satisfaction rating: ⭐⭐⭐⭐⭐

#### RECORDING TIPS
- Satisfied, conclusive tone
- Show the official permit clearly
- Explain the construction sign purpose
- Emphasize time savings strongly
- Professional, impressive closing
- Hold on timeline comparison for 5 seconds

---

## POST-PRODUCTION NOTES

### Editing Checklist
- [ ] Add intro title: "Building Permit Application"
- [ ] Insert GIS map animations (zoom, pan, highlight)
- [ ] Add zoning overlay graphics with legend
- [ ] Highlight validation checkmarks with animation
- [ ] Insert distance measurement arrows on map
- [ ] Add neighbor notification animation
- [ ] Insert BPMN workflow diagram overlay
- [ ] Add SBSYS integration flow graphic
- [ ] Show GDPR compliance badges prominently
- [ ] Insert timeline comparison chart
- [ ] Add background music (professional, technical)
- [ ] Balance audio levels
- [ ] Add Danish subtitles
- [ ] Add English subtitles (separate version)
- [ ] Color-grade for map clarity

### Graphics to Create
1. GIS zoning map with color-coded zones and legend
2. Property boundary visualization with measurements
3. Neighbor notification radius circle (25m)
4. Distance measurement arrows with dimensions
5. BPMN workflow diagram (5 stages)
6. SBSYS integration flow diagram
7. GDPR compliance badge collection
8. Access log visualization
9. Timeline comparison chart (manual vs. ÅbenForms)
10. Building permit PDF mockup
11. Construction site sign (A3 poster)

### Screen Captures Needed
- Full application flow (all steps)
- GIS map interface with property highlighted
- Zoning validation results panel
- Document upload interface with validation
- Neighbor identification and notification
- BPMN workflow visualization
- SBSYS integration dashboard
- GDPR compliance dashboard
- Access log table
- Final permit PDF
- Construction site sign

### Annotations to Add
- Circle property on GIS map
- Highlight zone type on map
- Box validation results (green checkmarks)
- Arrow pointing to automatic neighbor detection
- Circle BPMN workflow stages
- Highlight SBSYS integration status
- Box GDPR compliance badges
- Point to access log entries
- Circle time savings statistic

---

## UNIQUE FEATURES TO EMPHASIZE

### 1. GIS Zoning Validation (XFlow doesn't have this)
- Automatic property identification
- Real-time zoning checks
- Building coverage calculation
- Distance to boundary measurements
- Height restriction validation
- Interactive map visualization

### 2. Automatic Neighbor Notification
- GIS-based neighbor identification
- Automated letter generation
- Digital Post integration
- Objection tracking
- Full audit trail

### 3. Multi-Stage Intelligent Workflow
- AI-powered document validation
- Automatic case distribution
- Parallel processing
- Conditional logic
- BPMN orchestration

### 4. SBSYS Integration
- Real-time data synchronization
- Bi-directional updates
- Case status tracking
- Document archival

### 5. GDPR Compliance by Design
- Access logging
- Data minimization
- Automatic anonymization
- Citizen transparency
- Right to erasure

---

## COMPARISON WITH XFLOW

| Feature | XFlow | ÅbenForms |
|---------|-------|-----------|
| GIS Zoning Validation | ✗ |  Automatic |
| Neighbor Notification | Manual |  Automatic |
| BPMN Workflow Engine | Limited |  Full BPMN 2.0 |
| SBSYS Integration | API only |  Real-time sync |
| GDPR Compliance Tools | Basic |  Advanced |
| Processing Time | 4-8 weeks | 10-14 days |

**Key advantages**: GIS integration, automation, speed, compliance

---

## TECHNICAL TALKING POINTS

### For IT Managers
- "GIS integration uses industry-standard WMS/WFS protocols"
- "BPMN 2.0 workflow engine supports complex orchestration"
- "REST API for SBSYS integration"
- "PostgreSQL with PostGIS for spatial data"
- "Redis caching for map performance"

### For Planners/Inspectors
- "Automatic validation saves 2 hours per case"
- "GIS eliminates manual measurements"
- "Neighbor notification fully automated"
- "Full audit trail for legal compliance"
- "Mobile-friendly for site inspections"

### For Citizens/Architects
- "Submit applications 24/7"
- "Real-time status updates"
- "Immediate validation feedback"
- "Transparent process"
- "Faster approvals"

---

## ALTERNATIVE SCENARIOS

### Error Scenarios (Optional)

Show what happens when validation fails:

1. Building coverage exceeds limit → Rejection with explanation
2. Too close to boundary → Suggested adjustment
3. Wrong zone type → Alternative application type suggested
4. Neighbor objection → Additional review stage triggered

### Mobile Demo (Optional)

- Show responsive GIS map on mobile
- Demonstrate document upload from phone camera
- Show status tracking on mobile

---

## DELIVERY FORMATS

### YouTube Version
- Resolution: 1920x1080, 30fps
- Chapters:
  - 0:00 Introduction
  - 0:25 Project Type Selection
  - 1:00 Property Identification & GIS
  - 1:45 Automatic Zoning Validation
  - 3:00 Document Upload
  - 4:00 Neighbor Notification
  - 4:45 Multi-Stage Workflow
  - 5:30 GDPR & Integration
  - 6:15 Permit Issuance

### Conference Presentation Version
- Extended technical deep-dive
- Include backend workflow designer view
- Show administrator dashboard
- Demonstrate API integration
- Include performance metrics

### Social Media Teaser (45 seconds)
- Focus on GIS map visualization
- Show automatic validation magic
- Highlight time savings
- End with "75% faster approvals"

---

## SCRIPT APPROVAL

- [ ] Reviewed by: _______________
- [ ] Technical accuracy verified: _______________
- [ ] GIS integration tested: _______________
- [ ] Legal requirements validated: _______________
- [ ] GDPR compliance reviewed: _______________
- [ ] Danish translation reviewed: _______________
- [ ] Ready for production: _______________

**Date**: _______________
**Approved by**: _______________
