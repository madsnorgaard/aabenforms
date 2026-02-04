# Video Script 02: Parking Permit Demo (5 minutes)

## Video Metadata
- **Duration**: 5 minutes
- **Target Audience**: Municipal staff, system administrators, citizens
- **Goal**: Demonstrate end-to-end parking permit workflow
- **Tone**: Friendly, tutorial-style, reassuring

---

## Technical Setup

### Recording Environment
- **Browser**: Chrome in Incognito mode (clean session)
- **Resolution**: 1920x1080
- **Screen recording**: 30fps with cursor highlighting
- **Test environment**: https://demo.aabenforms.dk

### Demo Account Credentials
- **Citizen (Test MitID)**:
  - CPR: 0101701234
  - Name: Jens Jensen
  - Address: Nørregade 12, 8000 Aarhus C
- **Payment (Test Card)**:
  - Card: 4111 1111 1111 1111
  - Expiry: 12/25
  - CVV: 123

### Pre-recording Checklist
- [ ] Clear browser data
- [ ] Test MitID test environment connection
- [ ] Verify Nets payment test gateway
- [ ] Check SMS simulator is running
- [ ] Prepare sample vehicle data (plate number: AB12345)
- [ ] Set system clock to 10:00 AM for consistent timing

### Visual Assets Needed
- Parking permit application icon
- Sample vehicle registration document (anonymized PDF)
- Sample parking zone map
- Generated permit PDF (will be created during demo)

---

## SCRIPT

### [0:00-0:20] Introduction (20 seconds)

#### NARRATION (Danish)
"Velkommen til denne demonstration af parkeringslicens-ansøgning i ÅbenForms. Vi skal se, hvordan en borger kan ansøge om en parkeringslicens på under 5 minutter - fra start til godkendt licens i hånden. Lad os komme i gang."

#### NARRATION (English)
"Welcome to this demonstration of the parking permit application in ÅbenForms. We'll see how a citizen can apply for a parking permit in under 5 minutes - from start to approved license in hand. Let's get started."

#### SCREEN ACTIONS
1. Start at ÅbenForms homepage: https://demo.aabenforms.dk
2. Show navigation menu
3. Hover over "Trafik & Parkering" category
4. Show "Parkeringslicens" option highlighted

#### VISUAL CALLOUTS
- Circle "Parkeringslicens" menu item
- Show "Populær" badge next to workflow
- Display estimated time: "5 minutter"
- Show difficulty level: "Let" (Easy)

#### RECORDING TIPS
- Calm, reassuring voice
- Slow mouse movements for clarity
- Pause 2 seconds on homepage before navigating

---

### [0:20-0:45] Starting the Application (25 seconds)

#### NARRATION (Danish)
"Først klikker borgeren på Parkeringslicens. Systemet viser en kort oversigt over, hvad man skal bruge: køretøjsoplysninger, dokumentation, og betalingskort. Borgeren kan se prisen på forhånd - 600 kroner for en årlig licens. Lad os klikke 'Start ansøgning'."

#### NARRATION (English)
"First, the citizen clicks on Parking Permit. The system shows a brief overview of what's needed: vehicle information, documentation, and payment card. The citizen can see the price upfront - 600 kroner for an annual license. Let's click 'Start application'."

#### SCREEN ACTIONS
1. Click "Parkeringslicens" option
2. Land on information page showing:
   - Required documents checklist
   - Process steps (4 steps visualization)
   - Price: 600 kr/year
   - Processing time: Immediate
3. Click "Start ansøgning" button (blue, prominent)

#### VISUAL CALLOUTS
- Highlight checklist items:
  -  Køretøjsregistrering
  -  Folkeregisteradresse i parkeringszone
  -  Betalingskort
- Box the price: **600 kr/år**
- Show progress indicator: "Trin 0 af 4"
- Arrow pointing to "Start ansøgning" button

#### RECORDING TIPS
- Read checklist items clearly
- Emphasize "immediate" processing
- Use cursor to point at each checklist item

---

### [0:45-1:30] MitID Authentication (45 seconds)

#### NARRATION (Danish)
"Nu bliver borgeren bedt om at logge ind med MitID. Dette sikrer, at vi kender borgerens identitet og kan automatisk hente adresseoplysninger fra folkeregistret. I denne demo bruger vi MitID test-miljø."

#### NARRATION (English)
"Now the citizen is asked to log in with MitID. This ensures we know the citizen's identity and can automatically retrieve address information from the civil registration system. In this demo, we're using the MitID test environment."

#### SCREEN ACTIONS
1. MitID login prompt appears
2. Click "Log ind med MitID"
3. Redirect to MitID test page
4. Show MitID interface:
   - Enter CPR: 0101701234
   - Click "Næste"
   - Show notification simulation (app approval)
   - Click "Godkend" in simulator
5. Redirect back to ÅbenForms
6. Show welcome message: "Velkommen, Jens Jensen"

#### VISUAL CALLOUTS
- Highlight MitID logo (trusted authentication)
- Show security badge: "Sikker login"
- Circle CPR input field
- Show loading spinner during authentication
- Checkmark animation when login succeeds
- Display user info fetched:
  - Navn: Jens Jensen
  - CPR: 0101701234
  - Adresse: Nørregade 12, 8000 Aarhus C

#### RECORDING TIPS
- Explain MitID briefly for non-technical viewers
- Keep test environment transitions smooth
- Pause 2 seconds on welcome message
- Emphasize security and automation

---

### [1:30-2:30] Form Completion - Vehicle Information (60 seconds)

#### NARRATION (Danish)
"Nu er vi inde i selve ansøgningen. Først skal borgeren indtaste køretøjsoplysninger. Systemet validerer registreringsnummeret i realtid mod Motorregistret. Lad os indtaste AB12345."

#### NARRATION (English)
"Now we're in the actual application. First, the citizen must enter vehicle information. The system validates the registration number in real-time against the Motor Registry. Let's enter AB12345."

#### SCREEN ACTIONS
1. Form appears: "Trin 1 af 4 - Køretøjsoplysninger"
2. Type in "Registreringsnummer" field: **AB12345**
3. Show validation:
   - Loading spinner appears
   - Green checkmark appears
   - Auto-filled data:
     - Mærke: Volkswagen
     - Model: Golf
     - Årgang: 2020
     - Brændstof: Benzin
4. Select "Parkeringszone": **Zone 2 - Nørregade området**
5. Show zone map overlay with highlighted area
6. Enter "Parkeringssted" (optional): **Nørregade 12, gade**
7. Click "Næste"

#### VISUAL CALLOUTS
- Highlight registration number validation flow:
  - Input → API call → Validation → Auto-fill
- Show real-time validation icon (green checkmark)
- Zoom in on zone map showing Zone 2 boundaries
- Display zone info tooltip:
  - "Zone 2: 600 kr/år"
  - "Omfatter: Nørregade, Vestergade, Skolegade"
- Show field validation states:
  - Required fields marked with *
  - Valid input: green border
  - Invalid input: red border (demonstrate briefly)

#### RECORDING TIPS
- Type slowly and clearly
- Pause when auto-fill happens to let viewers see it
- Explain what's happening: "Notice the system automatically filled in the vehicle details"
- Point cursor at each auto-filled field
- Show zone map for 3 seconds minimum

---

### [2:30-3:15] Form Completion - Address Verification (45 seconds)

#### NARRATION (Danish)
"I trin 2 verificerer systemet, at borgerens adresse ligger i den valgte parkeringszone. Da vi loggede ind med MitID, er adressen allerede udfyldt - Nørregade 12, 8000 Aarhus C. Systemet tjekker automatisk, at denne adresse ligger i Zone 2. Alt er i orden, så vi klikker videre."

#### NARRATION (English)
"In step 2, the system verifies that the citizen's address is in the selected parking zone. Since we logged in with MitID, the address is already filled in - Nørregade 12, 8000 Aarhus C. The system automatically checks that this address is in Zone 2. Everything is fine, so we click continue."

#### SCREEN ACTIONS
1. Form appears: "Trin 2 af 4 - Adressebekræftelse"
2. Show pre-filled address information:
   - Navn: Jens Jensen
   - Adresse: Nørregade 12
   - Postnr: 8000
   - By: Aarhus C
3. Show automatic zone validation:
   - Loading check animation
   - Green checkmark: " Adresse bekræftet i Zone 2"
4. Show additional info field (optional):
   - Label: "Yderligere oplysninger"
   - Placeholder: "F.eks. lejlighedsnummer"
   - Type: "1. sal, tv"
5. Click "Næste"

#### VISUAL CALLOUTS
- Highlight MitID data icon showing data source
- Show validation badge: "Automatisk verificeret"
- Display zone match confirmation in green box:
  - " Din adresse er godkendt til Zone 2"
- Show progress bar: 50% complete
- Animate checkmark when validation succeeds

#### RECORDING TIPS
- Emphasize the automation: "Notice we didn't have to type anything"
- Point out the MitID integration benefit
- Speak confidently about the validation
- Keep pace steady, not rushing

---

### [3:15-4:00] Payment Processing (45 seconds)

#### NARRATION (Danish)
"Nu kommer vi til betalingen. Borgeren kan se en klar oversigt over prisen - 600 kroner for en årlig licens. Vi bruger Nets til sikker betalingshåndtering. Lad os indtaste testkort-oplysningerne."

#### NARRATION (English)
"Now we come to payment. The citizen can see a clear overview of the price - 600 kroner for an annual license. We use Nets for secure payment processing. Let's enter the test card details."

#### SCREEN ACTIONS
1. Form appears: "Trin 3 af 4 - Betaling"
2. Show payment summary box:
   - Parkeringslicens Zone 2: 600 kr
   - Administrationsgebyr: 0 kr
   - I alt: **600 kr**
3. Nets payment form loads (embedded iframe)
4. Enter card details:
   - Kortnummer: 4111 1111 1111 1111
   - Udløb: 12/25
   - CVV: 123
   - Navn: Jens Jensen
5. Click "Betal 600 kr"
6. Show processing animation (2 seconds)
7. Show success message: " Betaling gennemført"
8. Show receipt number: #PKG-2024-001234

#### VISUAL CALLOUTS
- Highlight Nets logo (trusted payment provider)
- Box the total amount prominently
- Show security badges:
  - PCI DSS compliant
  - SSL encrypted
  - 3D Secure
- Display processing animation (spinning icon)
- Show success checkmark (green, animated)
- Highlight receipt number for record-keeping

#### RECORDING TIPS
- Type card number with spacing for clarity
- Pause during processing animation
- Express relief/satisfaction when payment succeeds
- Point out security features verbally
- Note: "In production, this would use real payment gateway"

---

### [4:00-4:30] PDF Generation & SMS Confirmation (30 seconds)

#### NARRATION (Danish)
"Betalingen er godkendt! Nu genererer systemet automatisk parkeringslicensen som en PDF. Borgeren modtager også en SMS-bekræftelse på sit telefonnummer. Lad os se licensen."

#### NARRATION (English)
"Payment approved! Now the system automatically generates the parking license as a PDF. The citizen also receives an SMS confirmation to their phone number. Let's see the license."

#### SCREEN ACTIONS
1. Show success page: "Trin 4 af 4 - Bekræftelse"
2. Display confirmation message:
   - " Din parkeringslicens er godkendt!"
   - "Licensnummer: PL-2024-AH-001234"
   - "Gyldig fra: 02-02-2024"
   - "Gyldig til: 01-02-2025"
3. Show action buttons:
   - **"Download licens (PDF)"** (primary button)
   - "Send til e-mail" (secondary)
   - "Print licens" (secondary)
4. Show SMS notification (simulated popup):
   - "Din parkeringslicens PL-2024-AH-001234 er godkendt. Download på demo.aabenforms.dk/licenser"
5. Click "Download licens (PDF)"
6. PDF opens in new tab

#### VISUAL CALLOUTS
- Animate success checkmark (bounce effect)
- Highlight license number in large, bold text
- Show validity dates in clear date format
- Display QR code on confirmation page (links to license)
- Show SMS notification popup with phone animation
- Highlight download button with pulse effect

#### RECORDING TIPS
- Express satisfaction: "And that's it!"
- Emphasize the speed: "All in under 5 minutes"
- Show the SMS clearly (zoom in if needed)
- Keep PDF visible for review

---

### [4:30-4:50] PDF License Review (20 seconds)

#### NARRATION (Danish)
"Her er den genererede parkeringslicens. Den indeholder alle nødvendige oplysninger: licensnummer, køretøjsdata, parkeringszone, gyldighed, og en QR-kode til digital verifikation. Borgeren kan printe den eller gemme den digitalt."

#### NARRATION (English)
"Here is the generated parking license. It contains all necessary information: license number, vehicle data, parking zone, validity period, and a QR code for digital verification. The citizen can print it or save it digitally."

#### SCREEN ACTIONS
1. Show PDF document clearly:
   - Header: Aarhus Kommune logo + "Parkeringslicens"
   - License number: PL-2024-AH-001234
   - Citizen info: Jens Jensen, Nørregade 12
   - Vehicle info: AB12345, Volkswagen Golf
   - Zone: Zone 2 - Nørregade området
   - Validity: 02-02-2024 til 01-02-2025
   - QR code (bottom right)
   - Footer: Issue date, signature line
2. Scroll through PDF to show all sections
3. Zoom in on QR code

#### VISUAL CALLOUTS
- Circle the license number
- Highlight validity dates
- Point to QR code and explain: "For digital verification"
- Show municipal logo (official document)
- Box the zone information

#### RECORDING TIPS
- Scroll slowly through PDF
- Point out each section methodically
- Professional tone: "Official document"
- Zoom level should make text readable

---

### [4:50-5:00] Closing & Time Savings (10 seconds)

#### NARRATION (Danish)
"Og det var det! En komplet parkeringslicens-ansøgning på under 5 minutter. Sammenlignet med manuelle processer, der kan tage dage, sparer ÅbenForms både borgerne og kommunen enormt meget tid. Tak fordi du så med."

#### NARRATION (English)
"And that's it! A complete parking permit application in under 5 minutes. Compared to manual processes that can take days, ÅbenForms saves both citizens and the municipality enormous amounts of time. Thanks for watching."

#### SCREEN ACTIONS
1. Return to ÅbenForms dashboard
2. Show "Mine ansøgninger" page with completed application:
   - Status:  Godkendt
   - Type: Parkeringslicens
   - Dato: 02-02-2024
   - Actions: Download, View, Archive
3. Fade to end card showing:
   - Time saved: **5 minutes vs. 3-5 days**
   - Citizen satisfaction: ⭐⭐⭐⭐⭐
   - Next video: "Marriage Ceremony Booking Demo"

#### VISUAL CALLOUTS
- Highlight "Godkendt" status with green badge
- Show time comparison graphic:
  - Manual process: 3-5 days ⏰
  - ÅbenForms: 5 minutes ⚡
- Display satisfaction rating
- Show call-to-action: "Watch next demo →"

#### RECORDING TIPS
- Satisfied, conclusive tone
- Emphasize time savings clearly
- Hold end card for 5 seconds
- Smooth fade to black

---

## POST-PRODUCTION NOTES

### Editing Checklist
- [ ] Add intro title: "Parking Permit Demo"
- [ ] Add step indicators (Trin 1/4, 2/4, etc.) as overlays
- [ ] Highlight form fields as they're filled
- [ ] Add zoom effect on validation checkmarks
- [ ] Insert zone map graphic overlay
- [ ] Add SMS notification animation
- [ ] Show PDF generation animation
- [ ] Add time-lapse effect if typing is slow
- [ ] Insert time comparison graphic at end
- [ ] Add background music (subtle, professional)
- [ ] Balance audio levels
- [ ] Add Danish subtitles
- [ ] Add English subtitles (separate version)
- [ ] Add "Next video" end card with clickable link

### Graphics to Create
1. Step progress indicator (1/4, 2/4, 3/4, 4/4)
2. Parking zone map with Zone 2 highlighted
3. MitID authentication flow diagram
4. Payment security badges (PCI, SSL, 3D Secure)
5. SMS notification mockup
6. Time savings comparison chart
7. Satisfaction rating stars

### Screen Captures Needed
- Full application flow (all 4 steps)
- MitID login sequence
- Nets payment gateway
- Generated PDF license
- SMS confirmation
- Dashboard with completed application

### Annotations to Add
- Arrow pointing to "Start ansøgning" button
- Circle around validation checkmarks
- Box around price (600 kr)
- Highlight zone map boundaries
- Point to auto-filled fields
- Circle QR code on PDF

---

## ALTERNATIVE SCENARIOS

### Error Handling Demo (Optional 30-second extension)

Show what happens when validation fails:

1. Enter invalid registration number: "XX99999"
2. Show error message: "Registreringsnummer ikke fundet"
3. Show helpful hint: "Kontroller nummeret og prøv igen"
4. Correct the input
5. Show successful validation

**NARRATION**: "Hvis borgeren indtaster forkerte oplysninger, viser systemet klare fejlbeskeder og hjælp til at rette det."

### Mobile Version Demo (Optional 1-minute extension)

Show responsive design:

1. Switch to mobile view (375x667)
2. Show same workflow on mobile
3. Highlight mobile-friendly features:
   - Large touch targets
   - Simplified navigation
   - Mobile payment optimization
   - Easy PDF download

**NARRATION**: "Ansøgningen virker selvfølgelig også perfekt på mobil."

---

## DELIVERY FORMATS

### YouTube Version
- Resolution: 1920x1080, 30fps
- Format: MP4 (H.264)
- Chapters:
  - 0:00 Introduction
  - 0:20 Starting Application
  - 0:45 MitID Login
  - 1:30 Vehicle Information
  - 2:30 Address Verification
  - 3:15 Payment
  - 4:00 Confirmation & PDF
  - 4:50 Time Savings Summary

### Training Version (Extended)
- Add error handling scenarios
- Add mobile demonstration
- Include backend admin view
- Show reporting dashboard

### Social Media Teaser (30 seconds)
- Cut: 0:00-0:20 + 2:30-2:45 + 4:50-5:00
- Add captions for sound-off viewing
- Square format for Instagram/Facebook

---

## SCRIPT APPROVAL

- [ ] Reviewed by: _______________
- [ ] Technical accuracy verified: _______________
- [ ] Test environment prepared: _______________
- [ ] Demo data verified: _______________
- [ ] Danish translation reviewed: _______________
- [ ] Ready for production: _______________

**Date**: _______________
**Approved by**: _______________
