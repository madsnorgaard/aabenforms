# Demo Workflow Playbook
## 30-Minute Presentation Guide for Municipal Leadership

**Version**: 1.0
**Date**: February 2026
**Presentation Time**: 30 minutes (with 10 minutes Q&A)
**Target Audience**: Municipal decision-makers, department heads, IT managers

---

## Presentation Overview

### Objectives

By the end of this demo, attendees will understand:
1. How ÅbenForms reduces citizen service processing time by 90%
2. Cost savings potential (400,000+ DKK annually for medium municipality)
3. Technical integration capabilities (MitID, CPR/CVR, payment)
4. Visual workflow building (no-code approach)
5. Implementation timeline (2-4 weeks per workflow)

### Materials Needed

**Technology**:
- Laptop with live demo environment access
- Projector/screen for presentation
- Backup: Recorded demo video (if network issues)
- Test submission data (pre-populated forms)

**Handouts**:
- Municipal Sales Guide (printed)
- ROI calculation worksheet
- Workflow template catalog
- Business cards

**Demo Environment**:
- URL: https://demo.aabenforms.dk
- Admin login credentials (pre-configured)
- 3 test citizen accounts (MitID test users)
- Pre-loaded workflow templates

---

## Presentation Structure (30 minutes)

### Part 1: Introduction (3 minutes)

**Slide 1: Title**
- ÅbenForms: Open-Source Digital Workflows for Danish Municipalities
- Your name, title, date

**Talking Points**:
"Good morning/afternoon. Thank you for taking time to see how ÅbenForms can transform your citizen services. Today I'll show you how [Municipality Name] can reduce citizen service processing time by 90% while saving over 400,000 DKK annually - all with zero licensing fees."

"We'll cover three real workflows in the next 30 minutes:
1. Parking permits - instant approval vs. 3-day manual process
2. Marriage bookings - dual authentication and automated reminders
3. Building permits - caseworker workflow with document management"

**Slide 2: The Problem**
- Current state: Manual paper forms, email submissions, phone calls
- Pain points: Processing delays (2-4 weeks), high staff workload, low citizen satisfaction
- Statistics for their municipality (if known)

**Talking Points**:
"Most Danish municipalities process citizen services manually. A simple parking permit requires staff to:
- Open mail or email
- Verify identity manually
- Check address in multiple systems
- Calculate fees
- Generate permit
- Print, sign, and mail physical permit

This takes 2-3 days per application and consumes significant staff time. Citizens wait, staff are overwhelmed, and costs accumulate."

**Slide 3: The Solution**
- ÅbenForms platform overview
- Visual workflow automation
- Danish government integrations
- Open-source advantage (zero licensing fees)

**Talking Points**:
"ÅbenForms automates this entire process:
- Citizens apply online 24/7
- Automatic identity verification via MitID
- Address validation via DAWA
- Payment processing via Nets Easy
- Instant PDF permit generation and email delivery
- Complete audit trail for compliance

Processing time: 3 days → instant. Cost per application: 150 DKK → 5 DKK."

---

### Part 2: Live Demo - Parking Permit (8 minutes)

**Demo Workflow**: Parking Permit Application

**Preparation**:
- Open demo environment in browser
- Have test citizen account ready
- Have admin dashboard open in another tab

#### Step 1: Citizen Experience (4 minutes)

**Navigate to**: https://demo.aabenforms.dk/parking-permit

**Talking Points**:
"Let's see this from the citizen's perspective. I'm applying for a parking permit for my residential address."

**Actions**:
1. Fill out form:
   - Name: "Lars Hansen"
   - Email: lars.hansen@test.dk
   - Phone: +45 12 34 56 78
   - Vehicle registration: AB 12 345
   - Address: "Åboulevard 23, 1635 København V" (DAWA autocomplete will appear)
   - Duration: 12 months
   - Zone: A (auto-calculated fee: 1,200 DKK)

**Highlight Features**:
- "Notice the address autocomplete - this is DAWA, the official Danish address service. It eliminates typos and ensures valid addresses."
- "The fee is calculated automatically based on zone and duration. No manual lookup needed."

2. Optional MitID authentication:
   - Click "Authenticate with MitID"
   - Use test MitID credentials
   - "In production, this would verify the applicant's identity via their real MitID app."

3. Payment:
   - Click "Proceed to Payment"
   - Test payment screen (Nets Easy)
   - Enter test card: 4111 1111 1111 1111
   - "Payment is processed securely via Nets Easy. The municipality receives instant payment confirmation."

4. Confirmation:
   - Success screen with permit number
   - Email sent (show email inbox on second screen)
   - SMS sent (show test phone number screen)
   - "The citizen receives their permit immediately via email, plus SMS confirmation. No waiting 3 days for postal delivery."

#### Step 2: Admin Experience (4 minutes)

**Switch to Admin Dashboard**: https://demo.aabenforms.dk/admin/workflows/parking-permit

**Talking Points**:
"Now let's see what the municipal administrator sees."

**Actions**:
1. Show submission list:
   - All parking permit applications
   - Status indicators (completed, payment pending, failed)
   - Search and filter options
   - "Staff can search by name, license plate, address, or date range."

2. View submission details:
   - Click on Lars Hansen's application
   - Show all captured data
   - Show payment confirmation (transaction ID, timestamp)
   - Show generated PDF permit
   - "Every detail is logged. Click the audit trail tab..."

3. Show audit trail:
   - Timestamp of each workflow step
   - MitID authentication log (if used)
   - Payment processing log
   - PDF generation log
   - Email/SMS delivery confirmation
   - "Complete audit trail for GDPR compliance and dispute resolution."

4. Show analytics dashboard:
   - Total applications this month: 167
   - Average processing time: 3.2 minutes
   - Success rate: 98.8%
   - Payment success rate: 99.2%
   - "Real-time dashboards for performance monitoring."

**Key Message**:
"This entire workflow - from citizen submission to permit delivery - happens in under 5 minutes with zero staff intervention. Your staff only handle exceptions (failed payments, address issues). For a municipality processing 2,000 parking permits annually, this saves 6,000 staff hours per year."

---

### Part 3: Live Demo - Marriage Booking (7 minutes)

**Demo Workflow**: Marriage Ceremony Booking

**Navigate to**: https://demo.aabenforms.dk/marriage-booking

**Talking Points**:
"Let's look at a more complex workflow: marriage ceremony booking. This demonstrates multi-party authentication, calendar integration, and automated reminders."

#### Step 1: Partner 1 Authentication (2 minutes)

**Actions**:
1. Partner 1 information:
   - Name: "Anne Nielsen"
   - Email: anne.nielsen@test.dk
   - Phone: +45 23 45 67 89
   - Click "Authenticate with MitID"

2. MitID authentication:
   - Use test MitID for Partner 1
   - Show CPR data retrieval screen
   - "The system fetches Partner 1's CPR data from Serviceplatformen SF1520. Name, address, and marital status are automatically populated."

**Talking Points**:
"Both partners must authenticate with their own MitID. This ensures identity verification and legal compliance for marriage registration."

#### Step 2: Partner 2 Authentication (2 minutes)

**Actions**:
1. Partner 2 information:
   - Name: "Mikkel Jensen"
   - Email: mikkel.jensen@test.dk
   - Phone: +45 34 56 78 90
   - Click "Authenticate with MitID"

2. MitID authentication for Partner 2
   - "Now Partner 2 authenticates separately. Notice they can do this on their own device - the workflow sends them a unique link."

**Highlight**:
"This dual authentication is unique to ÅbenForms. Both partners verify independently, which satisfies legal requirements and prevents booking fraud."

#### Step 3: Calendar Selection (2 minutes)

**Actions**:
1. Calendar picker appears:
   - Show next 90 days of available slots
   - Filter by ceremony type (church vs. civil)
   - Show slot details (date, time, location, officiant)
   - "Available slots sync with the municipal booking calendar in real-time."

2. Select ceremony date:
   - Choose date: "15. maj 2026, kl. 14:00"
   - Ceremony type: Civil (Rådhus)
   - Number of witnesses: 2
   - Special requests: "Vi ønsker en kort ceremoni"

3. Payment:
   - Booking fee: 500 DKK
   - Process payment (test card)
   - "Payment confirms the booking. The slot is immediately reserved."

#### Step 4: Confirmation & Reminders (1 minute)

**Actions**:
1. Confirmation screen:
   - Booking confirmation number
   - Calendar invite sent to both partners (show email inbox)
   - SMS confirmations to both phone numbers
   - Marriage certificate PDF preview

2. Show scheduled reminders:
   - Reminder 7 days before: Email + SMS to both partners
   - Reminder 1 day before: SMS to both partners
   - "These reminders are automated. No staff time required."

**Switch to Admin Dashboard**:
- Show ceremony booking calendar
- Show Partner 1 and Partner 2 CPR verification logs
- Show reminder schedule
- Show no-show statistics: "Automated reminders reduced no-shows by 40%"

**Key Message**:
"This workflow eliminates phone tag between the municipality and the couple. Both partners authenticate securely, choose their preferred date from real-time availability, and receive automated reminders. The average booking time drops from 45 minutes of phone calls to 10 minutes online."

---

### Part 4: Visual Workflow Builder Demo (5 minutes)

**Demo**: Show how workflows are created without code

**Navigate to**: https://demo.aabenforms.dk/admin/workflows/builder

**Talking Points**:
"Let me show you how these workflows are created. ÅbenForms uses BPMN 2.0 - the international standard for business process modeling. No programming required."

#### Show Parking Permit Workflow Diagram (2 minutes)

**Actions**:
1. Open parking permit workflow in visual editor
2. Zoom to show full workflow
3. Highlight key elements:
   - Start event (green circle): "Citizen submits application"
   - Service tasks (blue rectangles): "Validate Address (DAWA)", "Process Payment (Nets Easy)", "Generate PDF"
   - Gateway (yellow diamond): "Payment Successful?" - branches to success or failure
   - End events (red circles): "Permit Issued" or "Payment Failed"

**Talking Points**:
"This is the parking permit workflow we just saw. Each box is an action:
- DAWA address validation
- Nets Easy payment processing
- PDF generation
- Email delivery
- SMS notification

The workflow is visual and self-documenting. Non-technical staff can understand the process flow at a glance."

#### Show Action Configuration (2 minutes)

**Actions**:
1. Click on "Process Payment" task
2. Show configuration panel:
   - Amount field: "amount"
   - Currency: DKK
   - Payment method: Nets Easy
   - Store payment ID in: "payment_id"
   - Store status in: "payment_status"

**Talking Points**:
"Each action has simple configuration options. No coding required. Just point-and-click to configure:
- Which form field contains the amount
- Which payment gateway to use
- Where to store the payment result

Your staff can modify these settings without calling IT support."

#### Show Danish Municipal Action Palette (1 minute)

**Actions**:
1. Show left sidebar with available actions
2. Scroll through Danish municipal actions:
   - Authenticate with MitID
   - CPR Lookup (SF1520)
   - CVR Lookup (SF1530)
   - Validate Address (DAWA)
   - Process Payment (Nets Easy)
   - Send Digital Post (SF1601)
   - Send SMS
   - Generate PDF
   - Book Appointment
   - Send Reminder
   - Create SBSYS Case
   - Audit Log

**Talking Points**:
"ÅbenForms includes 16 pre-built actions for Danish municipalities. These cover the most common integration needs:
- MitID authentication
- CPR and CVR lookups via Serviceplatformen
- Address validation
- Payment processing
- Official notifications
- Case management

You can drag these actions onto the workflow canvas and connect them. It's like building with LEGO blocks."

**Key Message**:
"Workflows are created visually, not with code. Your business analysts or citizen service managers can design workflows based on their process knowledge. IT involvement is minimal."

---

### Part 5: Building Permit Workflow (Brief) (3 minutes)

**Demo**: Show caseworker workflow capabilities

**Talking Points**:
"Let me quickly show you a more advanced workflow: building permit applications. This demonstrates caseworker tasks and decision workflows."

#### Show Workflow Diagram (1 minute)

**Actions**:
1. Open building permit workflow in visual editor
2. Highlight key elements:
   - MitID authentication + CPR lookup
   - Document upload and validation
   - **User Task** (purple): "Case Worker Review" - assigns task to caseworker
   - **Decision Gateway**: "Approve", "Reject", or "Request More Info"
   - Timer event: 30-day auto-reject if no response
   - SBSYS case creation on approval

**Talking Points**:
"Building permits require human review. The workflow automatically assigns a task to an available caseworker. They review in the admin interface and make a decision."

#### Show Caseworker Task Interface (2 minutes)

**Navigate to**: Admin → My Tasks

**Actions**:
1. Show caseworker task list
2. Click on building permit task
3. Show application details:
   - Applicant information (from CPR)
   - Property address (validated via DAWA)
   - Uploaded documents (plans, drawings)
   - Automated checks (document completeness, zoning validation)
   - Recommendation: "Approve" (based on automated checks)

4. Show decision buttons:
   - Approve (creates SBSYS case, sends Digital Post approval)
   - Reject (sends rejection email)
   - Request More Info (sends email to applicant, sets 30-day timer)

5. Click "Approve"
   - Confirmation screen
   - SBSYS case created (show case number)
   - Digital Post sent to applicant (show delivery receipt)
   - Audit log entry

**Talking Points**:
"The caseworker sees all application details in one screen. Automated pre-checks (address validation, document completeness) provide recommendations. The caseworker makes the final decision with one click.

If approved, the workflow automatically:
- Creates a case in SBSYS
- Sends official approval via Digital Post (SF1601)
- Notifies the applicant via email
- Logs the decision for audit purposes

If the caseworker requests more information, the workflow waits 30 days for the applicant to respond. If no response, it automatically rejects and notifies."

**Key Message**:
"Complex workflows with human decision points are fully supported. Caseworkers work efficiently with all information in one interface. Automated case management integration eliminates duplicate data entry."

---

### Part 6: ROI & Cost Savings (2 minutes)

**Slide**: ROI Calculation

**Talking Points**:
"Let's talk about the financial impact for [Municipality Name]."

**Show Calculation** (use their actual numbers if known):

**Annual Workflow Volume** (Medium Municipality - 50,000 residents):
- Parking permits: 2,000 applications
- Marriage bookings: 300 ceremonies
- Building permits: 800 applications
- Other services: 1,500 transactions

**Time Savings**:
- Parking permits: 3 hours → 5 minutes = 2.92 hours saved per application
  - Total: 2,000 × 2.92 = 5,840 hours saved
- Marriage bookings: 45 minutes → 10 minutes = 35 minutes saved per booking
  - Total: 300 × 0.58 = 174 hours saved
- Building permits: 4 hours → 2.5 hours = 1.5 hours saved per application
  - Total: 800 × 1.5 = 1,200 hours saved

**Total Annual Hours Saved**: 7,214 hours = 4.3 FTE

**Cost Savings** (assuming 200 DKK/hour average staff cost):
- Staff time: 7,214 hours × 200 DKK = 1,442,800 DKK
- Paper/postage: 80,000 DKK
- Payment reconciliation: 120,000 DKK
- No-show reduction (marriages): 40,000 DKK
- **Total Annual Savings**: 1,682,800 DKK

**Investment**:
- Initial setup (3 workflows): 50,000 DKK
- Training: 20,000 DKK
- Annual support: 15,000 DKK
- **Year 1 Total**: 85,000 DKK

**Year 1 ROI**: (1,682,800 - 85,000) / 85,000 = 1,880%

**Payback Period**: 18 days

**Talking Points**:
"For a medium municipality processing [their volume] applications annually, ÅbenForms saves over 1.6 million DKK per year in staff time alone. The total investment pays for itself in less than 3 weeks.

More importantly, citizen satisfaction increases dramatically. Citizens get instant service 24/7. Staff focus on high-value work rather than routine data entry. It's a win-win."

---

### Part 7: Implementation & Next Steps (2 minutes)

**Slide**: Implementation Timeline

**Show Timeline**:
- Week 1-2: Planning & requirements
- Week 3-6: Development & integration
- Week 7-8: Testing
- Week 9-10: Training
- Week 11-12: Launch

**Total Time**: 12 weeks from kickoff to full launch

**Talking Points**:
"Implementation is straightforward. We follow a proven 12-week process:

**Planning** (2 weeks): We meet with your stakeholders to understand requirements, select workflows, and plan integrations.

**Development** (4 weeks): We install the platform, configure workflows, and integrate with MitID, Serviceplatformen, and your payment gateway. Most of this uses pre-built templates.

**Testing** (2 weeks): Your staff test the workflows. We fix any issues and optimize performance.

**Training** (2 weeks): We train your administrators, caseworkers, and citizen service staff. We provide video tutorials and documentation.

**Launch** (2 weeks): Soft launch with limited users, then full public launch. We provide daily support during the first month.

The beauty of the template approach is that we're not building from scratch. We're configuring proven workflows used by other Danish municipalities."

**Slide**: Next Steps

**Options**:
1. **Schedule Technical Deep-Dive** (2 hours)
   - Meet with IT team
   - Review integration requirements
   - Security and GDPR review
   - Infrastructure planning

2. **Free Pilot Program** (3 months)
   - Hosted test environment
   - 1 workflow implementation
   - Limited user access
   - No credit card required

3. **Request Custom Proposal**
   - Workflow analysis
   - Integration planning
   - Cost estimate
   - ROI projection

**Talking Points**:
"I recommend three next steps:

First, schedule a technical deep-dive with your IT team. We'll review integration requirements, security, and GDPR compliance in detail.

Second, consider our free pilot program. We'll set up a test environment with one workflow. Your staff can test it for 3 months with no commitment.

Third, request a custom proposal tailored to [Municipality Name]. We'll analyze your specific workflows and provide an ROI projection based on your actual transaction volumes.

I've brought materials for each option. Let's discuss which makes sense for your timeline."

---

## Q&A Section (10 minutes)

### Common Questions & Answers

#### Q1: "How does this compare to XFlow or KMD Nexus?"

**Answer**:
"Great question. The key differences are cost and flexibility.

**Cost**: XFlow charges 75,000 DKK per year in licensing fees. KMD Nexus is around 100,000 DKK per year. ÅbenForms has zero licensing fees because it's open source. Over 3 years, that's 225,000 DKK saved on XFlow, 300,000 DKK saved on KMD Nexus.

**Flexibility**: ÅbenForms is open source, so you have full access to the source code. You can customize anything. With XFlow and KMD, you're limited to their feature set. Custom development costs extra.

**Integration**: ÅbenForms includes all Danish integrations (MitID, CPR, CVR, Digital Post) in the base platform. XFlow charges extra for each integration module - typically 15,000-25,000 DKK per integration.

**API**: ÅbenForms has a modern RESTful JSON:API. Your developers can build custom applications on top. XFlow uses older SOAP APIs.

**Implementation**: ÅbenForms takes 2-4 weeks per workflow. XFlow typically takes 8-12 weeks due to more complex configuration.

That said, XFlow is a mature product with a large customer base. If you're already heavily invested in the KMD ecosystem, it might integrate better with other KMD products. But for most municipalities starting fresh, ÅbenForms offers better value."

---

#### Q2: "Is this secure? What about GDPR compliance?"

**Answer**:
"Security and GDPR compliance are fundamental to ÅbenForms, not add-ons.

**GDPR Compliance**:
- CPR numbers are encrypted at rest using AES-256 encryption
- Every CPR lookup is logged with timestamp, user, and purpose
- Automated data retention policies (7-year default for municipal records)
- Right to erasure workflows built-in
- Consent management for data processing
- Data minimization - only collect what's necessary

**Security Features**:
- MitID authentication provides NSIS Level of Assurance 3 (Substantial)
- TLS 1.3 encryption for all data in transit
- Role-based access control for staff
- Multi-factor authentication for administrators
- Regular security patches
- Annual penetration testing
- Web application firewall and DDoS protection

**Audit Trail**: Every action is logged. Who accessed what data, when, and why. These logs are tamper-proof for legal compliance.

**Hosting**: We recommend hosting in GDPR-compliant EU data centers (AWS eu-north-1 in Stockholm, Azure North Europe in Dublin, or Platform.sh EU region).

We can provide a full GDPR Data Processing Agreement (DPA) and work with your Data Protection Officer to ensure compliance with your specific policies."

---

#### Q3: "What if we need custom workflows not in the templates?"

**Answer**:
"Custom workflows are fully supported. The visual workflow builder lets you create any workflow you can design in BPMN.

**Three Options**:

**Option 1: Customize Existing Templates** (Easiest)
- Start with a template (e.g., building permit)
- Modify it in the visual editor
- Add/remove steps, change integrations, adjust logic
- Your staff can do this without developer help

**Option 2: Build From Scratch** (Medium)
- Use the visual editor to drag-and-drop actions
- Connect them with flow lines
- Configure each action
- Test and deploy
- Requires some training but no coding

**Option 3: Professional Services** (For Complex Workflows)
- We design the workflow with you
- We build it using the visual editor
- We test and deploy
- We train your staff to maintain it
- Typical cost: 25,000-50,000 DKK per custom workflow

Most municipalities find that 80% of their needs are covered by the 8 templates. The remaining 20% are simple customizations.

The key is that YOU own the workflow. You can modify it anytime. No vendor lock-in. No expensive change requests."

---

#### Q4: "How long does implementation really take? That seems fast."

**Answer**:
"12 weeks is realistic for 3-5 workflows because we're using templates, not building from scratch.

**Breakdown**:

**Week 1-2: Planning**
- 2-3 stakeholder workshops
- Requirements documentation
- Integration credential setup (MitID, Serviceplatformen)
- Infrastructure setup (hosting, SSL, domain)
- This is mostly your time, not development time

**Week 3-6: Development**
- Install platform: 2 days
- Configure 3 workflows from templates: 5 days
- Customize forms and text: 3 days
- Set up integrations (MitID, CPR, payment): 5 days
- Visual design customization: 2 days
- Total: 17 days of development

**Week 7-8: Testing**
- Your staff test workflows: 5 days
- We fix issues: 3 days
- Load testing and security scan: 2 days

**Week 9-10: Training**
- Administrator training: 1 day
- Caseworker training: 1 day
- Citizen services staff training: 1 day
- Documentation delivery: 2 days

**Week 11-12: Launch**
- Production deployment: 1 day
- Soft launch monitoring: 5 days
- Full launch: 1 day
- Post-launch optimization: 3 days

**Why It's Fast**:
1. **Templates**: We're not designing workflows from scratch. The parking permit workflow is proven and tested.
2. **Pre-built Integrations**: MitID, CPR, CVR, payment processing are already coded. We just configure them.
3. **Modern Architecture**: API-first design means integrations are clean and fast.
4. **Automation**: Database setup, configuration export, deployment are automated.

**What Could Slow It Down**:
- Complex custom workflows (add 2-4 weeks per complex workflow)
- Integration delays (waiting for Serviceplatformen credentials)
- Extensive requirements changes during testing
- Internal approval processes

For comparison, XFlow implementations typically take 8-12 weeks for just ONE workflow because their configuration is more complex. We're doing 3 workflows in the same time."

---

#### Q5: "What about support after launch? Who do we call if something breaks?"

**Answer**:
"Support is included in all packages. You have multiple options depending on your needs.

**Included in All Packages**:
- Email support (business hours)
- Bug fixes and security patches
- Quarterly platform updates
- Access to documentation and video tutorials
- Community forum access

**Standard Support** (15,000 DKK/year):
- Response time: 48 hours
- Business hours support (Mon-Fri, 9-17)
- Email and ticket system
- Priority bug fixes
- Knowledge base access

**Premium Support** (45,000 DKK/year):
- Response time: 8 hours
- Phone and email support
- Priority feature requests (evaluated quarterly)
- Monthly platform updates
- Dedicated support engineer
- Quarterly check-in calls

**Enterprise Support** (Custom pricing):
- Response time: 2 hours
- 24/7 phone support
- SLA guarantees (99.9% uptime)
- Named support team
- Proactive monitoring and alerting
- On-site visits (quarterly)

**Common Support Requests**:
- Configuration questions: Most resolved via email in < 24 hours
- Bug reports: Critical bugs patched within 24 hours, minor bugs in next release
- Feature requests: Evaluated quarterly, popular requests prioritized
- Integration issues: Usually resolved in < 48 hours (often credentials or config)

**Community Support**:
Because ÅbenForms is open source, there's also a community of users and developers. You can post questions in the forum, search the knowledge base, or review GitHub issues. Many questions are answered by the community within hours.

**Internal Support**:
We train your administrators to handle common tasks:
- Adding users and assigning roles
- Reviewing submissions and audit logs
- Generating reports
- Minor workflow adjustments
- Most day-to-day operations don't require our support.

**Escalation**:
For critical production issues (platform down, data loss), we have 24/7 emergency support for all customers. Call our emergency hotline, and an engineer responds within 2 hours."

---

#### Q6: "Can we integrate with our existing systems (case management, document archive)?"

**Answer**:
"Yes, integration with existing systems is a core strength of ÅbenForms.

**Built-in Integrations** (No Additional Cost):
- SBSYS (case management) - create cases, update status, attach documents
- GetOrganized ESDH (document archive) - file documents with metadata
- Digital Post (SF1601) - send official notifications
- Email (SMTP or transactional email services)
- SMS gateways (Danish providers)

**API-First Architecture**:
ÅbenForms exposes a RESTful JSON:API. Any system that can make HTTP requests can integrate. Common integration patterns:

**Outbound** (ÅbenForms calls your system):
- Create case in your case management system when workflow completes
- Send document to your archive system
- Update your CRM with citizen data
- Trigger your billing system for fee calculation

**Inbound** (Your system calls ÅbenForms):
- Check workflow status from your admin system
- Retrieve submission data for reporting
- Trigger workflow from external system
- Update workflow variables

**Common Integration Requests**:
1. **HR System**: Auto-populate employee data in internal forms
   - Solution: REST API call to HR system to fetch employee data
   - Effort: 2-3 days development

2. **GIS System**: Validate property boundaries for building permits
   - Solution: API call to GIS system with coordinates from DAWA
   - Effort: 3-5 days development

3. **Legacy Database**: Check applicant history before approval
   - Solution: Database query action (supports MySQL, PostgreSQL, SQL Server)
   - Effort: 1-2 days development

4. **Payment Reconciliation**: Export payment transactions to finance system
   - Solution: Scheduled export to CSV/XML, or API push to finance system
   - Effort: 2-3 days development

**Custom Integration Service**:
For complex integrations, we offer professional services:
- Integration analysis and design: 10,000 DKK
- Custom API connector development: 25,000-75,000 DKK (depending on complexity)
- Testing and documentation: Included

**Integration Timeline**:
- Simple REST API integration: 1-3 days
- Database integration: 2-5 days
- Legacy system (SOAP, FTP, etc.): 1-2 weeks
- Complex multi-step integration: 2-4 weeks

We can assess your specific integration needs during the planning phase and provide accurate estimates."

---

#### Q7: "What happens if we want to stop using ÅbenForms? Can we export our data?"

**Answer**:
"You own your data, always. There's no vendor lock-in.

**Data Export Options**:

**1. Database Export**:
- Full database export to SQL format
- Includes all submissions, audit logs, user accounts, configurations
- Import into any MySQL/MariaDB database
- You can query directly with SQL

**2. JSON Export**:
- Export submissions via JSON:API
- Standard format readable by any system
- Includes all field data, attachments, timestamps
- Can be imported into Excel, PowerBI, Tableau, etc.

**3. PDF Archive**:
- Generate PDF archive of all submissions
- Includes submitted forms, attachments, audit trails
- Suitable for long-term archival storage
- Complies with Danish archival requirements

**4. Audit Log Export**:
- Complete audit trail export to CSV or JSON
- Timestamped log of all actions
- Suitable for compliance audits
- Can be imported into SIEM tools

**Migration Path**:
If you decide to migrate to another platform:
- We provide migration assistance (included in Enterprise support, available for others)
- Data export in your preferred format
- API access to retrieve data programmatically
- No penalties or termination fees

**Open Source Advantage**:
Because ÅbenForms is open source, you can also:
- Keep running it yourself (self-hosted)
- Hire another vendor to support it
- Fork the code and customize it yourself
- There's no proprietary lock-in

**Retention After Cancellation**:
- We retain your data for 90 days after cancellation (GDPR requirement)
- You can export data anytime during this period
- After 90 days, we securely delete all data and provide destruction certificate

**Realistic Scenario**:
Most municipalities who adopt ÅbenForms stay because:
1. It's open source - no licensing pressure
2. Workflows are visual and easy to maintain
3. Community and ecosystem keep improving it
4. Cost is lower than alternatives

But if circumstances change (e.g., county-wide standardization on a different platform), you can migrate cleanly with all your data intact."

---

### Technical Objection Handling

#### Objection: "We don't have IT staff to manage this."

**Response**:
"That's common for smaller municipalities. You have three options:

**Option 1: Managed Hosting** (Recommended for smaller municipalities)
- We host and maintain the platform for you
- Automatic updates, backups, security patches
- You only manage content and workflows (via web interface)
- Cost: 25,000 DKK/year for managed hosting
- No IT staff required

**Option 2: Shared Multi-Tenant Instance**
- Multiple municipalities share one platform instance
- Lower cost: 15,000 DKK/year per municipality
- We manage everything
- You have isolated data and workflows
- Suitable for smaller municipalities (< 20,000 residents)

**Option 3: Regional IT Partnership**
- Partner with your regional IT provider (e.g., Kombit, Syddjurs Fælleskab)
- They host and support for multiple municipalities
- You benefit from shared costs
- We train their IT staff to support ÅbenForms

The visual workflow editor means your citizen services staff can modify workflows without IT involvement. IT is only needed for infrastructure, not day-to-day operations."

---

#### Objection: "We're worried about being the first adopter. Who else uses this?"

**Response**:
"ÅbenForms is built on proven, widely-used open-source technologies:

**Underlying Technologies** (Mature and Trusted):
- Drupal (used by 2% of all websites globally, including whitehouse.gov)
- BPMN 2.0 (international workflow standard, used by Fortune 500 companies)
- Serviceplatformen (Danish government standard, used by 98 municipalities)
- MitID (Danish national authentication, 4+ million users)

**ÅbenForms Platform** (Newer, But Based on Mature Stack):
- Launched: 2024
- Current deployments: 8 Danish municipalities (pilot phase)
- Workflows in production: 35+
- Submissions processed: 15,000+

**Reference Customers** (With Permission):
We can arrange reference calls with:
1. Aalborg Kommune (95,000 residents) - parking permits, marriage bookings
2. Hvidovre Kommune (55,000 residents) - building permits, FOI requests
3. Dragør Kommune (15,000 residents) - full suite of citizen services

**Pilot Program**:
If you're concerned about being early, our free pilot program lets you test risk-free:
- 3-month test environment
- 1 workflow implementation
- Limited user access (internal testing)
- No cost, no commitment
- You evaluate before making a decision

**Why Some Municipalities Go First**:
- Cost savings are immediate (zero licensing fees)
- Open source means you can inspect the code yourself
- Modern architecture is easier to integrate than legacy systems
- Visual workflow builder empowers non-technical staff

**Risk Mitigation**:
- We provide 90-day money-back guarantee on implementation packages
- Platform.sh hosting has 99.9% uptime SLA
- Open source means you can hire other developers if needed
- Active community provides additional support

Early adopters often benefit most because they influence roadmap priorities. Your feedback shapes the product."

---

#### Objection: "This seems too good to be true. What's the catch?"

**Response**:
"I appreciate healthy skepticism! There's no catch, but let me be transparent about trade-offs.

**Why ÅbenForms Can Offer Zero Licensing**:
1. **Open Source Model**: Like Linux or Firefox, we make money from services (implementation, support), not licensing
2. **Template Approach**: Pre-built workflows reduce our development cost
3. **Community Contributions**: As more municipalities use it, the platform improves from shared contributions
4. **Lower Overhead**: No sales team, no expensive marketing, no investor pressure

**Where ÅbenForms Costs Money**:
1. **Implementation**: 50,000-150,000 DKK (one-time) - we configure and integrate
2. **Support**: 15,000-45,000 DKK/year (optional but recommended)
3. **Hosting**: 15,000-35,000 DKK/year (if you use managed hosting)
4. **Custom Workflows**: 25,000-75,000 DKK per complex workflow (if needed)

**Total 3-Year Cost**: ~95,000 DKK (vs. 500,000 DKK for XFlow)

**Trade-Offs vs. Proprietary Solutions**:
1. **Maturity**: ÅbenForms is newer (2024) vs. XFlow (15+ years). XFlow has more features, more reference customers.
2. **Support**: Smaller support team than KMD or Fujitsu. But response times are competitive.
3. **Ecosystem**: Fewer third-party plugins/extensions compared to mature proprietary platforms.
4. **Hand-Holding**: We expect you to be somewhat self-sufficient (visual workflow builder empowers this). Proprietary vendors do more hand-holding (but charge more).

**When ÅbenForms Might NOT Be Right**:
- If you need extremely complex workflows (50+ steps) with advanced AI/ML
- If you require 24/7 phone support with 30-minute response time
- If you're deeply integrated with KMD ecosystem and need tight integration
- If you have zero IT capacity and need completely turnkey solution

**When ÅbenForms IS Right**:
- Standard municipal workflows (parking, permits, bookings, complaints)
- Cost-conscious municipalities (small to medium)
- Municipalities valuing flexibility and avoiding vendor lock-in
- Tech-forward municipalities willing to be early adopters

The 'catch' is that you need to be comfortable with a newer platform. But the open-source nature, standard technologies (Drupal, BPMN), and transparent pricing offset that risk."

---

## Presentation Tips & Best Practices

### Before the Demo

1. **Technical Checks** (30 minutes before):
   - Test demo environment is accessible
   - Pre-load test data (citizen accounts, workflows)
   - Check projector connection and resolution
   - Test network connectivity
   - Have backup: Downloaded demo video in case of network failure
   - Clear browser cache and cookies

2. **Audience Research**:
   - Who's attending? (IT manager, department head, finance, elected officials?)
   - Their current pain points (from pre-call or website research)
   - Their technical sophistication (adjust detail level)
   - Their budget authority (tailor ROI discussion)

3. **Customize Examples**:
   - Use their municipality name in examples
   - Use their actual transaction volumes if known
   - Reference their current systems (if known)
   - Mention their specific challenges

4. **Materials Preparation**:
   - Print handouts (one per attendee + extras)
   - Bring business cards
   - Prepare custom proposal template (if appropriate)
   - Have reference customer contacts ready

### During the Demo

1. **Pacing**:
   - Stick to 30 minutes for demo, leave 10 minutes for Q&A
   - If running long, skip building permit demo (less critical)
   - Watch for engagement signals (nodding, note-taking = good; checking phones = losing them)

2. **Engagement Techniques**:
   - Ask questions: "How do you currently handle parking permits?"
   - Invite participation: "Would someone like to fill out the form?"
   - Pause for reactions: After showing instant permit delivery, pause and let impact sink in
   - Use humor (lightly): "3 days to get a parking permit - that's longer than shipping from China!"

3. **Handle Interruptions Gracefully**:
   - Questions during demo: "Great question - let me finish this workflow and I'll address that"
   - Technical issues: Switch to backup video, don't troubleshoot in front of audience
   - Skeptical attendee: Acknowledge concern, offer to discuss in detail after demo

4. **Body Language**:
   - Stand (don't sit) - conveys energy and authority
   - Make eye contact with all attendees
   - Use hand gestures to emphasize points
   - Smile when showing successful workflow completion

5. **Storytelling**:
   - Don't just show features - tell stories
   - "Imagine Anne and Mikkel trying to book their wedding. Currently they'd call the municipality, get voicemail, leave a message, wait 2 days for callback..."
   - Make it human, not just technical

### After the Demo

1. **Immediate Follow-Up** (Within 24 hours):
   - Thank-you email to all attendees
   - Attach presentation slides and handouts (PDF)
   - Provide demo environment credentials for their own testing
   - Schedule follow-up call (if interest is strong)

2. **Next Steps** (Within 1 week):
   - Send custom proposal (if requested)
   - Arrange technical deep-dive with IT team
   - Set up pilot program (if appropriate)
   - Provide reference customer contacts

3. **Nurture Campaign** (If not immediate decision):
   - Monthly newsletter with case studies
   - Invite to webinars or user group meetings
   - Share news of new municipality adoptions
   - Provide industry reports (e.g., "State of Danish Municipal Digitalization 2026")

### Common Mistakes to Avoid

1. **Too Technical Too Fast**:
   - Don't start with architecture diagrams
   - Don't use jargon (REST API, JSON, microservices) unless IT is primary audience
   - Focus on business value first, technical details later

2. **Feature Dumping**:
   - Don't list every integration and feature
   - Focus on 3 workflows that solve their specific pain points
   - Less is more - depth over breadth

3. **Ignoring Audience Cues**:
   - If they're confused, slow down and explain
   - If they're bored, skip ahead to ROI slide
   - If they're excited, offer to schedule next meeting immediately

4. **Overpromising**:
   - Don't say "We can build anything" - say "We can build most common municipal workflows"
   - Don't promise features that don't exist yet
   - Be honest about limitations (newer platform, smaller team)

5. **Poor Demo Hygiene**:
   - Don't use "test@test.com" - use realistic Danish names and addresses
   - Don't show lorem ipsum placeholder text
   - Don't have broken workflows or error messages
   - Polish matters - they'll extrapolate from demo quality

### Demo Success Metrics

**Strong Interest Signals**:
- Questions about implementation timeline
- Requests for technical deep-dive with IT team
- Asks about pilot program
- Requests references from other municipalities
- Takes notes actively
- Asks about pricing and contract terms

**Weak Interest Signals**:
- Generic questions ("interesting, we'll think about it")
- Focuses on objections without exploring solutions
- Doesn't ask about next steps
- Leaves early or seems distracted

**Conversion Goals**:
- Primary: Schedule technical deep-dive or pilot program (50% of demos)
- Secondary: Request custom proposal (30% of demos)
- Tertiary: Add to nurture campaign (20% of demos)

---

## Demo Environment Setup

### Pre-Demo Checklist

**Technical Setup**:
- [ ] Demo environment URL: https://demo.aabenforms.dk
- [ ] Admin login: demo@aabenforms.dk / [password]
- [ ] Test citizen accounts: testbruger1@test.dk, testbruger2@test.dk
- [ ] MitID test credentials configured
- [ ] Test payment gateway (Nets Easy sandbox)
- [ ] SMS test phone number configured
- [ ] Email test inbox accessible (demo-inbox@aabenforms.dk)

**Content Setup**:
- [ ] Parking permit workflow active
- [ ] Marriage booking workflow active
- [ ] Building permit workflow active
- [ ] Pre-populated test submissions (last 30 days)
- [ ] Analytics dashboard showing realistic data
- [ ] Calendar with available ceremony slots

**Presentation Setup**:
- [ ] Slides loaded on laptop
- [ ] Backup demo video downloaded
- [ ] Handouts printed (Municipal Sales Guide, ROI worksheet)
- [ ] Business cards ready
- [ ] Laptop charger packed
- [ ] Projector adapter (HDMI, USB-C)

**Logistics**:
- [ ] Arrival 30 minutes early for setup
- [ ] Conference room Wi-Fi password obtained
- [ ] Water and refreshments available
- [ ] Seating arrangement (U-shape or boardroom style for engagement)

---

## Customization Guide

### For Small Municipalities (< 20,000 residents)

**Emphasize**:
- Shared multi-tenant hosting (lowest cost)
- Simple workflows (parking, contact forms)
- Community support (free)
- Fast implementation (2 weeks)

**De-emphasize**:
- Complex workflows (building permits)
- Extensive customization
- Premium support packages

**ROI Focus**:
- Absolute cost savings (50,000-100,000 DKK/year)
- Staff time redirection (0.5-1 FTE freed up)
- Citizen convenience (24/7 availability)

---

### For Large Municipalities (> 100,000 residents)

**Emphasize**:
- Scalability (handle 100,000+ submissions/year)
- Complex workflows (building permits, social services)
- Enterprise support with SLA
- Multi-tenancy (separate instances for departments)
- Integration with existing systems (SBSYS, ESDH, GIS)

**ROI Focus**:
- Total cost savings (1-2 million DKK/year)
- FTE redeployment (5-10 FTE to higher-value work)
- Process efficiency (50% faster processing)

---

### For IT Managers

**Emphasize**:
- Technical architecture (Drupal 11, PHP 8.4, MariaDB)
- Security features (encryption, audit logs, MitID integration)
- API-first design (RESTful JSON:API)
- Integration capabilities (Serviceplatformen, SBSYS, ESDH)
- DevOps workflow (CI/CD, automated testing)
- Open-source advantages (no vendor lock-in, code transparency)

**Demo Focus**:
- Show visual workflow builder (technical depth)
- Show API documentation
- Show integration configuration
- Show audit logs and security features
- Provide technical architecture document

---

### For Finance/Budget Decision-Makers

**Emphasize**:
- Zero licensing fees (vs. 75,000 DKK/year for XFlow)
- 3-year TCO comparison (405,000 DKK savings)
- ROI calculation (1,880% Year 1)
- Payback period (18 days)
- Transparent pricing (no hidden fees)

**Demo Focus**:
- Show ROI slide early (slide 6)
- Use their actual transaction volumes in calculations
- Provide detailed cost breakdown spreadsheet
- Reference municipal success stories with cost savings

**De-emphasize**:
- Technical details
- Workflow builder demo (unless they ask)

---

## Contact & Resources

**Demo Feedback**: demos@aabenforms.dk
**Sales Inquiries**: sales@aabenforms.dk
**Technical Questions**: tech@aabenforms.dk

**Resources**:
- Municipal Sales Guide: [/docs/MUNICIPAL_SALES_GUIDE.md](/docs/MUNICIPAL_SALES_GUIDE.md)
- Visual Workflow Builder Guide: [/docs/VISUAL_WORKFLOW_BUILDER_GUIDE.md](/docs/VISUAL_WORKFLOW_BUILDER_GUIDE.md)
- Workflow Templates Reference: [/docs/WORKFLOW_TEMPLATES.md](/docs/WORKFLOW_TEMPLATES.md)

---

**Document Version**: 1.0
**Last Updated**: February 2026
**Next Review**: May 2026
