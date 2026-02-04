# Video Script 03: Marriage Ceremony Booking Demo (5 minutes)

## Video Metadata
- **Duration**: 5 minutes
- **Target Audience**: Municipal staff, registry office administrators, engaged couples
- **Goal**: Demonstrate complex dual-authentication workflow with calendar integration
- **Tone**: Warm, celebratory, professional

---

## Technical Setup

### Recording Environment
- **Browser**: Chrome in Incognito mode
- **Resolution**: 1920x1080
- **Screen recording**: 30fps with cursor highlighting
- **Test environment**: https://demo.aabenforms.dk/vielse

### Demo Account Credentials

**Applicant 1 (Bride/Partner A)**:
- CPR: 0101851234
- Name: Anna Andersen
- Email: anna.andersen@example.dk
- Phone: +45 20 12 34 56

**Applicant 2 (Groom/Partner B)**:
- CPR: 0202881234
- Name: Bo Bertelsen
- Email: bo.bertelsen@example.dk
- Phone: +45 30 12 34 56

### Pre-recording Checklist
- [ ] Clear browser data
- [ ] Test dual MitID flow (need two devices or simulator)
- [ ] Verify calendar availability API is running
- [ ] Check email simulator is configured
- [ ] Prepare sample date: 3 weeks from recording date
- [ ] Set up witness information (2 witnesses required)
- [ ] Verify ceremony location data is loaded

### Visual Assets Needed
- Wedding ceremony icon/illustration
- Sample ceremony venue photos
- Calendar interface mockup
- Dual MitID authentication flow diagram
- Confirmation email template
- Reminder SMS template

---

## SCRIPT

### [0:00-0:20] Introduction (20 seconds)

#### NARRATION (Danish)
"Velkommen til demonstration af vielsesreservation i ÅbenForms. Dette er et godt eksempel på en kompleks workflow med dobbelt MitID-godkendelse, kalenderintegration, og automatiske påmindelser. Lad os se, hvordan et par kan booke deres bryllup online."

#### NARRATION (English)
"Welcome to the marriage ceremony booking demonstration in ÅbenForms. This is a great example of a complex workflow with dual MitID authentication, calendar integration, and automated reminders. Let's see how a couple can book their wedding online."

#### SCREEN ACTIONS
1. Start at ÅbenForms homepage
2. Navigate to "Borgerservice" → "Vielse"
3. Show landing page with romantic imagery:
   - Ceremony venue photos
   - Information about civil ceremonies
   - Requirements checklist
   - Available dates preview

#### VISUAL CALLOUTS
- Highlight "Vielse" option in menu
- Show complexity indicator: "Mellem" (Medium complexity)
- Display estimated time: "15-20 minutter"
- Show "Kræver 2 personer" badge (unique feature)

#### RECORDING TIPS
- Warm, welcoming tone (this is a happy occasion!)
- Slow pan over venue photos
- Emphasize this is a complex workflow
- Background music: gentle, celebratory

---

### [0:20-0:45] Requirements Overview (25 seconds)

#### NARRATION (Danish)
"Før vi starter, viser systemet en klar oversigt over kravene. Begge parter skal være til stede under bookingen og logge ind med MitID. Vi skal vælge dato, tidspunkt, ceremonisted, og angive to vidner. Prisen er 1.200 kroner. Lad os starte ansøgningen."

#### NARRATION (English)
"Before we start, the system shows a clear overview of the requirements. Both parties must be present during booking and log in with MitID. We need to choose date, time, ceremony location, and specify two witnesses. The price is 1,200 kroner. Let's start the application."

#### SCREEN ACTIONS
1. Show requirements page with sections:

   **Krav til ansøgere:**
   - ✓ Begge parter skal være fyldt 18 år
   - ✓ Ingen af parterne må være gift i forvejen
   - ✓ Mindst én part skal være folkeregistreret i kommunen

   **Hvad skal I bruge:**
   - ✓ MitID for begge parter
   - ✓ Oplysninger om 2 vidner (navn, CPR)
   - ✓ Betalingskort (1.200 kr)

   **Bookning:**
   - ✓ Vælg mellem 3 ceremoniesteder
   - ✓ Ledige datoer 3 uger frem
   - ✓ Ceremonier afholdes tirs-lør kl. 10-16

2. Click "Start booking" button

#### VISUAL CALLOUTS
- Highlight dual MitID requirement (unique complexity)
- Box the price: **1.200 kr**
- Show calendar icon with available dates
- Highlight witness requirement
- Show "2 personer nødvendigt" badge with user icons

#### RECORDING TIPS
- Read requirements clearly and methodically
- Emphasize "both parties must be present"
- Professional but warm tone
- Pause on requirements page for viewer comprehension

---

### [0:45-1:45] Partner A MitID Authentication (60 seconds)

#### NARRATION (Danish)
"Nu logger den første part ind med MitID. Dette er Anna Andersen. Systemet henter automatisk hendes personlige oplysninger fra folkeregistret, herunder civilstand og bopæl. Dette sikrer, at hun opfylder kravene for at blive gift i kommunen."

#### NARRATION (English)
"Now the first party logs in with MitID. This is Anna Andersen. The system automatically retrieves her personal information from the civil registration system, including marital status and residence. This ensures she meets the requirements to get married in the municipality."

#### SCREEN ACTIONS
1. Show "Trin 1a - Part 1 godkendelse"
2. Display prompt: "Part 1, log venligst ind med MitID"
3. Click "Log ind med MitID"
4. MitID flow:
   - Enter CPR: 0101851234
   - Approve in MitID app simulator
   - Return to ÅbenForms
5. Show retrieved data:
   - **Navn**: Anna Andersen
   - **CPR**: 010185-1234
   - **Adresse**: Vestergade 45, 8000 Aarhus C
   - **Civilstand**: Ugift ✓
   - **Folkeregister**: Aarhus Kommune ✓
6. Show validation checkmarks:
   - ✓ Over 18 år
   - ✓ Ugift
   - ✓ Folkeregistreret i kommunen
7. Show contact information pre-filled:
   - Email: anna.andersen@example.dk
   - Telefon: +45 20 12 34 56
8. Click "Bekræft og fortsæt"

#### VISUAL CALLOUTS
- Highlight "Part 1" badge (blue)
- Show MitID authentication flow diagram overlay
- Animate validation checkmarks (green, one by one)
- Circle marital status verification: "Ugift ✓"
- Highlight residence verification: "Aarhus Kommune ✓"
- Show data flow: MitID → CPR Register → ÅbenForms
- Box pre-filled contact information

#### RECORDING TIPS
- Explain why dual authentication is needed
- Emphasize automatic validation (no manual checks needed)
- Point out each validation checkpoint
- Pause after each checkmark appears
- Professional, thorough tone

---

### [1:45-2:45] Partner B MitID Authentication (60 seconds)

#### NARRATION (Danish)
"Nu skal den anden part også logge ind. Dette kan ske på samme enhed - systemet sikrer, at begge parter er fysisk til stede og samtykker til vielsen. Her logger Bo Bertelsen ind."

#### NARRATION (English)
"Now the second party must also log in. This can happen on the same device - the system ensures both parties are physically present and consent to the marriage. Here, Bo Bertelsen logs in."

#### SCREEN ACTIONS
1. Show "Trin 1b - Part 2 godkendelse"
2. Display message:
   "Anna Andersen er godkendt ✓
   Nu skal part 2 logge ind med MitID"
3. Show "Log ud Anna" option (grayed out for security)
4. Click "Part 2: Log ind med MitID"
5. MitID flow for Partner B:
   - Enter CPR: 0202881234
   - Approve in MitID app simulator
   - Return to ÅbenForms
6. Show retrieved data:
   - **Navn**: Bo Bertelsen
   - **CPR**: 020288-1234
   - **Adresse**: Nørregade 12, 8200 Aarhus N
   - **Civilstand**: Ugift ✓
   - **Folkeregister**: Aarhus Kommune ✓
7. Show validation checkmarks:
   - ✓ Over 18 år
   - ✓ Ugift
   - ✓ Folkeregistreret i kommunen
   - ✓ Ikke i familie med part 1 (automatic check)
8. Show contact information:
   - Email: bo.bertelsen@example.dk
   - Telefon: +45 30 12 34 56
9. Show summary panel:
   "Begge parter godkendt ✓
   - Part 1: Anna Andersen
   - Part 2: Bo Bertelsen"
10. Click "Fortsæt til booking"

#### VISUAL CALLOUTS
- Highlight "Part 2" badge (green, different from Part 1)
- Show both partners' data side by side
- Animate checkmark when both are validated
- Display relationship validation: "Ikke i familie ✓"
- Show security feature: Can't proceed without both logins
- Highlight summary panel showing both names

#### RECORDING TIPS
- Explain the dual consent mechanism
- Emphasize security: "Both must be present"
- Show the relationship validation briefly
- Reassuring tone: "Both partners approved"
- Celebrate this milestone: "Great! Both partners verified"

---

### [2:45-3:45] Calendar & Venue Selection (60 seconds)

#### NARRATION (Danish)
"Nu kommer den sjove del - at vælge dato og sted for vielsen. Systemet viser kun ledige tider baseret på rådhuset kalender. Vi kan vælge mellem tre smukke ceremoniesteder. Lad os vælge Rådhuset, gammel festsal, om tre uger."

#### NARRATION (English)
"Now comes the fun part - choosing the date and location for the ceremony. The system only shows available times based on the town hall calendar. We can choose between three beautiful ceremony locations. Let's choose the Town Hall, old festive hall, in three weeks."

#### SCREEN ACTIONS
1. Show "Trin 2 - Vælg dato og sted"
2. Display calendar interface:
   - Month view with available dates highlighted in green
   - Unavailable dates grayed out
   - Selected date: 3 weeks from today (e.g., 23-02-2024)
3. Click on available date: **23. februar 2024**
4. Show time slot selector:
   - 10:00 (Optaget)
   - 11:00 (Ledig) ✓
   - 12:00 (Ledig)
   - 13:00 (Optaget)
   - 14:00 (Ledig)
   - 15:00 (Ledig)
5. Select time: **11:00**
6. Show venue selector (3 options with photos):

   **Option 1: Rådhuset, Gammel Festsal** (Selected)
   - Capacity: 50 personer
   - Style: Klassisk, historisk
   - Photo: Beautiful historic hall

   **Option 2: Rådhuset, Moderne Sal**
   - Capacity: 30 personer
   - Style: Moderne, minimalistisk

   **Option 3: Borgercenter, Glassal**
   - Capacity: 20 personer
   - Style: Lys, intim

7. Click on "Gammel Festsal"
8. Show booking summary:
   - Dato: Fredag, 23. februar 2024
   - Tid: 11:00-11:30 (30 min ceremoni)
   - Sted: Rådhuset, Gammel Festsal
   - Pris: 1.200 kr

#### VISUAL CALLOUTS
- Highlight available dates in green on calendar
- Show real-time availability (simulated API call)
- Zoom in on venue photos as they're selected
- Display capacity and style information
- Box the selected time slot
- Show booking summary panel with all details
- Animate venue selection with smooth transitions

#### RECORDING TIPS
- Excited, celebratory tone: "This is where the magic happens!"
- Pause on each venue photo for visual appeal
- Explain availability checking: "Real-time calendar integration"
- Smooth mouse movements when browsing calendar
- Show venue photos clearly (3 seconds each)

---

### [3:45-4:20] Witness Information (35 seconds)

#### NARRATION (Danish)
"Nu skal vi angive oplysninger om de to vidner, som skal være til stede ved vielsen. Dette er et lovkrav. Vi indtaster deres navne og CPR-numre. Systemet validerer, at de er over 18 år."

#### NARRATION (English)
"Now we need to provide information about the two witnesses who must be present at the ceremony. This is a legal requirement. We enter their names and CPR numbers. The system validates that they are over 18 years old."

#### SCREEN ACTIONS
1. Show "Trin 3 - Vidner"
2. Display form with 2 witness sections:

   **Vidne 1:**
   - Fornavn: **Carla**
   - Efternavn: **Christensen**
   - CPR: **1510901234**
   - Relation: **Veninde** (dropdown)
   - Validation: ✓ Over 18 år

   **Vidne 2:**
   - Fornavn: **David**
   - Efternavn: **Davidsen**
   - CPR: **2010851234**
   - Relation: **Ven** (dropdown)
   - Validation: ✓ Over 18 år

3. Show age validation (automatic from CPR)
4. Optional field: "Vil I have særlige ønsker?"
   - Checkbox: ✓ Vi medbringer egen musik
   - Text field: "Vi vil gerne have 'A Thousand Years' spillet"
5. Click "Fortsæt til betaling"

#### VISUAL CALLOUTS
- Highlight CPR validation flow
- Show age checkmark animation (green)
- Display relationship dropdown options:
  - Veninde/Ven
  - Familiemedlem
  - Kollega
  - Andet
- Box special requests section
- Show character counter on text field (0/500)

#### RECORDING TIPS
- Explain legal requirement clearly
- Type CPR numbers carefully (validation demo)
- Show the automatic age validation
- Warm tone for special requests: "Personal touches"
- Keep typing pace steady

---

### [4:20-4:45] Payment & Confirmation (25 seconds)

#### NARRATION (Danish)
"Nu betaler vi 1.200 kroner for vielsen. Efter betaling genererer systemet en bekræftelse og sender automatisk kalender-invitationer til begge parter og vidnerne. De modtager også SMS-påmindelser en uge før og én dag før ceremonien."

#### NARRATION (English)
"Now we pay 1,200 kroner for the ceremony. After payment, the system generates a confirmation and automatically sends calendar invitations to both parties and the witnesses. They also receive SMS reminders one week before and one day before the ceremony."

#### SCREEN ACTIONS
1. Show "Trin 4 - Betaling"
2. Payment summary:
   - Vielsesceremoni: 1.000 kr
   - Festsal leje: 200 kr
   - I alt: **1.200 kr**
3. Nets payment form (abbreviated for time):
   - Enter card: 4111 1111 1111 1111
   - Expiry: 12/25
   - CVV: 123
4. Click "Betal 1.200 kr"
5. Show processing (2 seconds)
6. Show success page: "✓ Jeres vielse er booket!"

#### VISUAL CALLOUTS
- Box total amount: **1.200 kr**
- Show Nets security badges
- Animate payment success checkmark
- Display booking confirmation:
  - Booking nr: VIE-2024-001234
  - Dato: 23. februar 2024, kl. 11:00
  - Sted: Rådhuset, Gammel Festsal
  - Parter: Anna Andersen & Bo Bertelsen

#### RECORDING TIPS
- Professional tone for payment
- Pause during processing animation
- Excited tone at success: "Congratulations!"
- Show booking number clearly

---

### [4:45-5:00] Automated Reminders & Closing (15 seconds)

#### NARRATION (Danish)
"Og det var det! Parret har nu booket deres vielse på under 15 minutter. De modtager straks en bekræftelsesmail med kalender-invitation, og systemet sender automatisk SMS-påmindelser. Alt er klart til den store dag. Tillykke!"

#### NARRATION (English)
"And that's it! The couple has now booked their wedding in under 15 minutes. They immediately receive a confirmation email with calendar invitation, and the system automatically sends SMS reminders. Everything is ready for the big day. Congratulations!"

#### SCREEN ACTIONS
1. Show confirmation page with actions:
   - Download bekræftelse (PDF)
   - Tilføj til kalender (.ics file)
   - Send til email
   - Print bekræftelse
2. Show email notification (simulated):
   - Subject: "Bekræftelse af vielse - 23. februar 2024"
   - Attachment: Kalender-invitation.ics
   - Content: All booking details
3. Show SMS reminder preview (simulated):
   "Påmindelse: Jeres vielse er om 1 uge (23. feb kl. 11:00, Rådhuset). Se detaljer: demo.aabenforms.dk/vielse/VIE-2024-001234"
4. Show automated workflow timeline:
   - 📧 Straks: Bekræftelsesmail
   - 📅 1 uge før: SMS-påmindelse
   - 📱 1 dag før: SMS-påmindelse
   - 📧 Efter vielse: Ægteskabsattest

#### VISUAL CALLOUTS
- Highlight calendar invitation file (.ics)
- Show email with all details
- Display SMS notification on phone mockup
- Show automated reminder timeline graphic
- Animate timeline with checkmarks
- Show celebration icon/confetti animation

#### RECORDING TIPS
- Warm, congratulatory tone
- Show the automation clearly
- Emphasize the convenience
- Hold on reminder timeline for 3 seconds
- Celebratory closing: "Everything automated!"

---

## POST-PRODUCTION NOTES

### Editing Checklist
- [ ] Add intro title: "Marriage Ceremony Booking"
- [ ] Add romantic background music (subtle, tasteful)
- [ ] Insert dual MitID flow diagram animation
- [ ] Add "Part 1" and "Part 2" badges overlay
- [ ] Highlight validation checkmarks with animation
- [ ] Insert venue photo slideshow
- [ ] Add calendar interaction highlights
- [ ] Show payment processing animation
- [ ] Insert email and SMS notification mockups
- [ ] Add automated reminder timeline graphic
- [ ] Add celebration animation at end (confetti or hearts)
- [ ] Balance audio levels
- [ ] Add Danish subtitles
- [ ] Add English subtitles (separate version)
- [ ] Add end card with next video link

### Graphics to Create
1. Dual MitID authentication flow diagram
2. Venue photos (3 ceremony locations)
3. Calendar interface with availability highlighting
4. Email confirmation template mockup
5. SMS reminder notification mockup
6. Automated workflow timeline graphic
7. Celebration animation (confetti/hearts)
8. Booking confirmation certificate design

### Screen Captures Needed
- Full booking flow (all 4 steps)
- Dual MitID login sequences
- Calendar selection interface
- Venue photos and descriptions
- Witness information form
- Payment confirmation
- Email and SMS notifications
- Reminder timeline

### Annotations to Add
- "Part 1" and "Part 2" labels during authentication
- Arrow pointing to validation checkmarks
- Circle around marital status verification
- Highlight available calendar dates
- Box venue selection options
- Point to witness age validation
- Circle booking confirmation number

---

## UNIQUE FEATURES TO EMPHASIZE

### Dual MitID Authentication
- Both parties must consent
- Prevents unauthorized bookings
- Ensures physical presence
- Validates legal requirements automatically

### Real-time Calendar Integration
- Shows only available slots
- Prevents double-booking
- Updates instantly across system
- Integrates with municipal calendar

### Automated Reminders
- Email confirmation with .ics file
- SMS reminders (1 week, 1 day before)
- Witness notifications
- Post-ceremony follow-up

### Complex Workflow Orchestration
- Multi-party authentication
- Sequential validation steps
- Conditional logic (age, marital status, residence)
- Integration with multiple systems (CPR, payment, calendar)

---

## COMPARISON WITH MANUAL PROCESS

### Traditional Process (Pre-ÅbenForms)
1. Couple calls municipality (wait time: varies)
2. Schedule in-person appointment
3. Both parties visit office with ID
4. Fill out paper forms
5. Staff manually checks CPR register
6. Staff checks calendar availability
7. Manual payment processing
8. Staff mails confirmation letter
9. Manual reminder phone calls
**Total time: 1-2 weeks, multiple visits**

### ÅbenForms Process
1. Online booking (any time, any device)
2. Dual MitID authentication (automatic validation)
3. Real-time calendar selection
4. Instant payment processing
5. Automated confirmations and reminders
**Total time: 15 minutes, no visits required**

**Time savings: 95%**
**Staff time saved: 2 hours per booking**

---

## ALTERNATIVE SCENARIOS

### Error Handling Demo (Optional)

Show what happens when validation fails:

1. Partner already married → Error message
2. Under 18 years old → Cannot proceed
3. Calendar slot becomes unavailable → Alternative suggested
4. Payment fails → Retry option

### Mobile Version (Optional)

- Show responsive design on mobile
- Demonstrate both parties using separate phones
- Show calendar interface on small screen

---

## DELIVERY FORMATS

### YouTube Version
- Resolution: 1920x1080, 30fps
- Chapters:
  - 0:00 Introduction
  - 0:20 Requirements Overview
  - 0:45 Partner A Authentication
  - 1:45 Partner B Authentication
  - 2:45 Calendar & Venue Selection
  - 3:45 Witness Information
  - 4:20 Payment & Confirmation
  - 4:45 Automated Reminders

### Training Version
- Extended with backend admin view
- Show how staff manages ceremony calendar
- Demonstrate rescheduling process
- Show reporting and analytics

### Social Media Teaser (30 seconds)
- Romantic, celebratory tone
- Focus on venue selection and confirmation
- End with "Book your wedding online in 15 minutes"

---

## SCRIPT APPROVAL

- [ ] Reviewed by: _______________
- [ ] Technical accuracy verified: _______________
- [ ] Legal requirements validated: _______________
- [ ] Demo environment prepared: _______________
- [ ] Danish translation reviewed: _______________
- [ ] Sensitivity review (inclusive language): _______________
- [ ] Ready for production: _______________

**Date**: _______________
**Approved by**: _______________
