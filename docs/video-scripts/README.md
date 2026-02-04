# ÅbenForms Video Production Guide

Complete guide for recording, editing, and publishing the ÅbenForms demo video series.

---

## Overview

This directory contains detailed scripts for 5 professional demo videos showcasing the ÅbenForms platform. Each script includes exact narration (Danish and English), screen actions, timing markers, visual callouts, and production notes.

### Video Series

1. **Platform Overview** (3 minutes) - Introduction and value proposition
2. **Parking Permit Demo** (5 minutes) - Simple workflow demonstration
3. **Marriage Booking Demo** (5 minutes) - Complex dual-authentication workflow
4. **Building Permit Demo** (7 minutes) - Advanced features (GIS, SBSYS integration)
5. **Visual Builder Tutorial** (10 minutes) - Workflow creation without coding

**Total series runtime**: ~30 minutes
**Target audience**: Municipal decision-makers, IT managers, system administrators
**Production level**: Professional/semi-professional

---

## Equipment Recommendations

### Minimum Requirements

**Computer:**
- Processor: Intel i5 or equivalent (8th gen or newer)
- RAM: 16GB minimum (32GB recommended)
- Storage: 100GB free space for recordings and editing
- OS: Windows 10/11, macOS 10.15+, or Linux

**Display:**
- Resolution: 1920x1080 minimum (native resolution preferred)
- Size: 24" or larger for comfortable screen recording
- Color accuracy: IPS panel recommended for consistent colors

**Microphone:**
- USB condenser microphone (Blue Yeti, Audio-Technica AT2020USB+, Rode NT-USB)
- Alternatively: Lapel mic for more natural voice
- Avoid: Built-in laptop microphones (poor quality)

**Internet:**
- 50 Mbps+ download for demo environment access
- Stable connection (wired Ethernet preferred over WiFi)

### Professional Setup (Optional)

**Camera** (if adding picture-in-picture presenter):
- 1080p webcam (Logitech C920 or better)
- DSLR/mirrorless camera with HDMI output
- Ring light for consistent lighting

**Audio:**
- Audio interface (Focusrite Scarlett 2i2)
- Studio headphones (closed-back for monitoring)
- Acoustic treatment (foam panels or blanket fort)
- Pop filter for microphone

**Misc:**
- Green screen (for advanced compositing)
- Teleprompter software (for reading narration)
- Second monitor for script/notes

---

## Software Recommendations

### Screen Recording

**Option 1: OBS Studio** (FREE - Recommended)
- Platform: Windows, macOS, Linux
- Pros: Free, powerful, flexible, professional features
- Cons: Steeper learning curve
- Download: https://obsproject.com/

**OBS Settings for ÅbenForms:**
```
Video:
- Base Resolution: 1920x1080
- Output Resolution: 1920x1080
- FPS: 30 (sufficient for UI demonstrations)

Output:
- Encoder: x264 (CPU) or NVENC (GPU if available)
- Rate Control: CBR
- Bitrate: 10,000 - 15,000 Kbps
- Preset: High Quality
- Profile: high
- Keyframe Interval: 2

Audio:
- Sample Rate: 48 kHz
- Channels: Stereo
- Desktop Audio: Muted (no system sounds during narration)
- Microphone: Enabled, -12dB to -6dB levels
```

**Option 2: Camtasia** (PAID)
- Platform: Windows, macOS
- Pros: Easy to use, built-in editing, cursor effects
- Cons: $300 license
- Download: https://www.techsmith.com/camtasia.html

**Option 3: ScreenFlow** (PAID - macOS only)
- Platform: macOS
- Pros: Excellent for Mac, built-in editing
- Cons: macOS only, $169
- Download: https://www.telestream.net/screenflow/

**Option 4: SimpleScreenRecorder** (FREE - Linux)
- Platform: Linux
- Pros: Lightweight, reliable
- Download: https://www.maartenbaert.be/simplescreenrecorder/

### Video Editing

**Option 1: DaVinci Resolve** (FREE - Recommended)
- Platform: Windows, macOS, Linux
- Pros: Professional features, free version very capable, color grading
- Cons: Resource-intensive, learning curve
- Download: https://www.blackmagicdesign.com/products/davinciresolve

**Option 2: Adobe Premiere Pro** (PAID)
- Platform: Windows, macOS
- Pros: Industry standard, extensive features
- Cons: $22.99/month subscription
- Download: https://www.adobe.com/products/premiere.html

**Option 3: Final Cut Pro** (PAID - macOS only)
- Platform: macOS
- Pros: Excellent performance on Mac, magnetic timeline
- Cons: macOS only, $299 one-time
- Download: https://www.apple.com/final-cut-pro/

**Option 4: Kdenlive** (FREE)
- Platform: Windows, macOS, Linux
- Pros: Free, open-source, decent features
- Cons: Less polished than paid options
- Download: https://kdenlive.org/

### Graphics & Animations

**After Effects** (PAID):
- For advanced animations, transitions, callouts
- $22.99/month subscription

**Canva** (FREE/PAID):
- For title cards, thumbnails, simple graphics
- Free tier sufficient
- https://www.canva.com/

**Figma** (FREE/PAID):
- For UI mockups, diagrams, flowcharts
- Free tier sufficient
- https://www.figma.com/

### Audio Editing

**Audacity** (FREE):
- Noise reduction, audio cleanup
- https://www.audacityteam.org/

**Adobe Audition** (PAID):
- Professional audio post-production
- Included in Adobe Creative Cloud

### Utilities

**Cursor Highlighter**:
- **Windows**: PointerFocus (free trial)
- **macOS**: Mouseposé ($5)
- **Linux**: kmag or custom cursor theme

**Teleprompter**:
- **Cross-platform**: PromptSmart (free/paid)
- **Web-based**: Cueprompter (free, cueprompter.com)

**Color Picker**:
- For matching ÅbenForms brand colors
- **Cross-platform**: ColorSlurp, Just Color Picker

---

## Recording Workflow

### Phase 1: Pre-Production (1-2 days per video)

1. **Script Review**
   - Read script multiple times
   - Highlight difficult pronunciations
   - Practice Danish narration (if applicable)
   - Time yourself to match target duration

2. **Environment Setup**
   - Prepare demo environment (see RECORDING_CHECKLIST.md)
   - Load test data and accounts
   - Clear browser cache, close unnecessary apps
   - Test all integrations (MitID test, payment test, etc.)

3. **Technical Setup**
   - Configure recording software (see settings above)
   - Test microphone levels (-12dB to -6dB peak)
   - Test screen resolution (1920x1080)
   - Set up second monitor with script
   - Enable Do Not Disturb mode

4. **Dry Run**
   - Record 1-2 minute test segment
   - Check audio quality, video quality
   - Review cursor movements
   - Verify demo environment works smoothly

### Phase 2: Recording (2-4 hours per video)

**Best Practices:**

1. **Record in Segments**
   - Don't try to record entire 10-minute video in one take
   - Record by major sections (as outlined in scripts)
   - Easier to fix mistakes, less pressure

2. **Multiple Takes**
   - Record each segment 2-3 times
   - Choose best take in editing
   - Don't stop for minor mistakes (fix in post)

3. **Screen Recording Tips**
   - Close all unnecessary applications
   - Disable notifications (Do Not Disturb)
   - Hide desktop icons (clean background)
   - Use browser incognito mode (no extensions, clean session)
   - Type at moderate speed (not too fast, not too slow)
   - Slow, deliberate mouse movements
   - Pause 2 seconds before and after major actions
   - Don't rush transitions

4. **Narration Tips**
   - Speak clearly and at moderate pace
   - Smile while narrating (it comes through in voice)
   - Stay hydrated (water, no sugary drinks)
   - Take breaks every 30 minutes
   - Record in quiet environment (no traffic, no HVAC noise)
   - 6-12 inches from microphone (check manual)
   - Use pop filter to reduce plosives (P, B sounds)

5. **Common Mistakes to Avoid**
   - Don't narrate and perform actions simultaneously (record separately)
   - Don't rush through important sections
   - Don't use filler words (um, uh, så, altså)
   - Don't click before hover effect completes
   - Don't scroll too fast
   - Don't skip loading animations (they add realism)

### Phase 3: Post-Production (4-8 hours per video)

**Editing Workflow:**

1. **Rough Cut** (1-2 hours)
   - Import all footage into editing software
   - Arrange segments in order per script
   - Remove mistakes, long pauses
   - Trim dead space at start/end
   - Check overall pacing

2. **Audio Post** (1 hour)
   - Normalize audio levels (-3dB for narration)
   - Remove background noise (Audacity noise reduction)
   - Add background music (-18dB to -24dB, subtle)
   - Balance narration and music
   - Add audio transitions (fade in/out)
   - Check for pops, clicks, mouth sounds

3. **Visual Enhancements** (2-3 hours)
   - Add intro/outro cards (5 seconds each)
   - Insert graphics and annotations per script
   - Add cursor highlighting/enlargement
   - Add zoom effects on key UI elements
   - Add callout boxes, arrows, circles
   - Add transition effects between sections
   - Color correct for brand consistency

4. **Graphics & Animations** (1-2 hours per video)
   - Create title cards (per script requirements)
   - Create comparison charts
   - Create workflow diagrams
   - Add animated checkmarks
   - Add progress indicators
   - Create end cards with CTAs

5. **Subtitles** (1-2 hours)
   - Auto-generate subtitles (YouTube, Descript, or Rev.com)
   - Manually correct auto-generated text
   - Time subtitles precisely to narration
   - Export SRT files for Danish and English
   - Embed or upload separately

6. **Quality Control** (30 minutes)
   - Watch entire video without interruption
   - Check for typos in graphics
   - Verify timing of all callouts
   - Ensure audio levels consistent
   - Check color consistency
   - Test subtitle synchronization
   - Get feedback from colleague

7. **Export** (15-30 minutes)
   - Export settings (see Export Settings below)
   - Export Danish version
   - Export English version (if separate)
   - Create social media versions (see Deliverables)

---

## Export Settings

### YouTube Version (Primary)

**Video:**
- Codec: H.264
- Container: MP4
- Resolution: 1920x1080 (1080p)
- Frame Rate: 30 fps (constant)
- Bitrate: 12,000 - 15,000 Kbps (VBR, 2-pass)
- Profile: High
- Level: 4.2

**Audio:**
- Codec: AAC
- Sample Rate: 48 kHz
- Bitrate: 192 kbps (stereo)
- Channels: 2 (Stereo)

**File Naming:**
- `01_Platform_Overview_DA_1080p.mp4`
- `01_Platform_Overview_EN_1080p.mp4`

### Website Embed Version

**Video:**
- Same as YouTube but lower bitrate for faster loading
- Bitrate: 5,000 - 8,000 Kbps
- Consider 720p (1280x720) for smaller file size

**File Naming:**
- `01_Platform_Overview_DA_720p_web.mp4`

### Social Media Versions

**YouTube Shorts / TikTok / Instagram Reels:**
- Resolution: 1080x1920 (vertical, 9:16)
- Duration: 30-60 seconds (teaser from main video)
- Codec: H.264, MP4
- Frame Rate: 30 fps
- Bitrate: 8,000 Kbps
- Subtitles: Burned in (many view without sound)

**Instagram/Facebook Feed:**
- Resolution: 1080x1080 (square, 1:1)
- Duration: 30-45 seconds
- Same codec settings as above

**LinkedIn:**
- Resolution: 1920x1080 (horizontal, 16:9)
- Duration: 1-2 minutes (business-focused excerpt)
- Same codec settings as YouTube

---

## Background Music

### Recommended Music Sources

**Free (Royalty-Free):**
- **YouTube Audio Library**: Free, good variety, safe for YouTube
  - https://studio.youtube.com/channel/UC/music
- **Free Music Archive**: Open-source music
  - https://freemusicarchive.org/
- **Incompetech**: Kevin MacLeod's royalty-free music
  - https://incompetech.com/music/royalty-free/

**Paid (Subscription):**
- **Epidemic Sound**: $15/month, excellent quality
  - https://www.epidemicsound.com/
- **Artlist**: $16.60/month, high-quality, unlimited
  - https://artlist.io/
- **Musicbed**: Premium, $99/month
  - https://www.musicbed.com/

### Music Selection Guidelines

**Platform Overview (Video 01):**
- Style: Upbeat, modern corporate
- Mood: Confident, innovative, inspiring
- Tempo: 120-130 BPM
- Examples: "Inspiring Corporate", "Technology Innovation"

**Parking Permit & Marriage Booking (Videos 02-03):**
- Style: Ambient, subtle background
- Mood: Calm, helpful, friendly
- Tempo: 90-110 BPM
- Examples: "Soft Piano", "Gentle Tech"

**Building Permit (Video 04):**
- Style: Professional, technical
- Mood: Sophisticated, trustworthy
- Tempo: 100-120 BPM
- Examples: "Corporate Technology", "Business Innovation"

**Visual Builder Tutorial (Video 05):**
- Style: Educational, light
- Mood: Focused, encouraging
- Tempo: 95-115 BPM
- Examples: "Learning Mode", "Educational Background"

**Audio Levels:**
- Narration: -3dB to -6dB (peak)
- Music: -18dB to -24dB (background, not distracting)
- Always duck music under narration (reduce by 6-10dB when speaking)

---

## Branding Guidelines

### Colors

**Primary Palette:**
- **Danish Blue**: #0051A5 (primary brand color)
- **Danish Red**: #C60C30 (secondary, accent)
- **White**: #FFFFFF (backgrounds, text)
- **Dark Gray**: #333333 (text, UI elements)

**Accent Colors:**
- **Yellow**: #FFB612 (highlights, callouts)
- **Green**: #2ECC71 (success, checkmarks)
- **Red**: #E74C3C (errors, warnings)
- **Light Blue**: #3498DB (information, links)

### Typography

**Headings:**
- Font: Montserrat Bold
- Fallback: Arial Bold, sans-serif
- Use for: Title cards, section headers
- Color: #0051A5 (Danish Blue) or #FFFFFF (on dark backgrounds)

**Body Text:**
- Font: Open Sans Regular
- Fallback: Arial, Helvetica, sans-serif
- Use for: Descriptions, subtitles, callouts
- Color: #333333 (dark text) or #FFFFFF (light text)

**Code/Technical:**
- Font: Fira Code
- Fallback: Consolas, Monaco, monospace
- Use for: Variable names, code snippets, technical details
- Color: #2ECC71 (success), #E74C3C (errors)

### Logo Usage

**ÅbenForms Logo:**
- Use high-resolution PNG with transparent background
- Minimum size: 200px width
- Place in top-left or center of title cards
- Always include "tagline" on first appearance: "Open Source til Danske Kommuner"

**Partner Logos:**
- MitID, Nets, SBSYS (when demonstrating integrations)
- Use official brand logos (request permission if needed)
- Maintain aspect ratio
- Minimum size: 100px width

### Visual Style

**Callouts & Annotations:**
- Use consistent style throughout series
- Arrows: Yellow (#FFB612), 4px stroke, rounded ends
- Circles: Yellow (#FFB612), 3px stroke, slight transparency
- Boxes: Danish Blue (#0051A5), 2px stroke, 10% fill opacity
- Text backgrounds: White with 80% opacity, 5px padding

**Transitions:**
- Keep simple and professional
- Fade (0.5-1 second) for most transitions
- Crossfade for scene changes
- Zoom for emphasis (1.2x scale, 0.5 seconds)
- Avoid flashy transitions (wipes, spins, etc.)

**Animations:**
- Smooth, professional motion
- Ease in/out (not linear)
- Typical duration: 0.3-0.8 seconds
- Checkmarks: Scale from 0 to 1 with slight bounce
- Progress bars: Fill left to right, 2 seconds
- Loading spinners: Rotate continuously, subtle

---

## Subtitles & Accessibility

### Subtitle Guidelines

**Format:**
- SRT (SubRip Subtitle) format for YouTube
- VTT (WebVTT) for website embed
- Max 2 lines per subtitle
- Max 42 characters per line (readability)
- Display time: Minimum 1.5 seconds per subtitle

**Timing:**
- Sync precisely with narration start/end
- Add 0.1s buffer before speech starts
- Hold 0.2s after speech ends (for reading)
- Don't overlap scene changes

**Style:**
- Font: Arial, Roboto, or Open Sans
- Size: 18-20pt (YouTube default)
- Color: White text with black background (80% opacity)
- Position: Bottom-center (default)

**Content:**
- Verbatim transcription of narration
- Include sound effects in brackets: [mouse click], [notification sound]
- Don't include music descriptions unless relevant
- Use proper punctuation and capitalization
- Danish: Follow Danish grammar and spelling rules
- English: Use US or UK English consistently (choose one)

### Creating Subtitles

**Automatic Generation (Quick):**
1. Upload video to YouTube (unlisted/private)
2. Wait for auto-generated subtitles (5-10 minutes)
3. Download auto-generated SRT
4. Manually correct errors
5. Re-upload corrected SRT

**Manual Creation (Accurate):**
1. Use subtitle editor:
   - **Subtitle Edit** (Windows, free): https://www.nikse.dk/subtitleedit
   - **Aegisub** (Cross-platform, free): https://aegisub.org/
   - **Kapwing** (Web-based, free/paid): https://www.kapwing.com/
2. Import video
3. Type subtitles while watching
4. Adjust timing precisely
5. Export SRT file

**Professional Service (Best):**
- **Rev.com**: $1.50/minute, 99% accuracy, 12-hour turnaround
- **Happy Scribe**: $0.25/minute (machine), $1.70/minute (human)
- **Amberscript**: Specialized in Danish, €0.17/minute (machine)

### Accessibility Checklist

- [ ] Subtitles in Danish (all Danish videos)
- [ ] Subtitles in English (all English videos or separate version)
- [ ] High contrast visuals (readable for color-blind viewers)
- [ ] Audio description track (optional, for visually impaired)
- [ ] Keyboard-navigable video player (if embedded)
- [ ] Transcript available in video description (YouTube)
- [ ] Alt text on video thumbnails (website)

---

## Publishing Workflow

### YouTube

**Video Upload:**
1. Log in to YouTube Studio
2. Click "Create" → "Upload videos"
3. Select video file
4. While uploading, configure:

**Title:**
- Format: "ÅbenForms: [Title] | [Language] | [Category]"
- Example: "ÅbenForms: Platform Overview | Dansk | Open Source Selvbetjening"
- Max 100 characters
- Include keywords: ÅbenForms, Kommune, Selvbetjening, Open Source

**Description:**
```
[2-3 sentence summary of video content]

ÅbenForms er Danmarks første open source digitale selvbetjeningsplatform,
bygget specifikt til danske kommuner. I denne video [specific content].

📋 Kapitel:
0:00 Introduction
0:30 [Chapter 1]
1:15 [Chapter 2]
... (all chapters)

🔗 Links:
• Demo: https://demo.aabenforms.dk
• Dokumentation: https://docs.aabenforms.dk
• GitHub: https://github.com/aabenforms/platform
• Website: https://aabenforms.dk

📧 Kontakt:
• Email: kontakt@aabenforms.dk
• Community: community.aabenforms.dk

#ÅbenForms #Kommune #Selvbetjening #OpenSource #Danmark #MitID #Digitalisering

[Full transcript below]
--- TRANSCRIPT ---
[Paste full narration text here]
```

**Tags:**
- ÅbenForms, Kommune, Selvbetjening, Open Source, Danmark, MitID
- XFlow alternative, Kommunal digitalisering, BPMN
- (Max 500 characters total)

**Thumbnail:**
- Resolution: 1280x720 (16:9 ratio)
- File size: Under 2MB
- Format: JPG or PNG
- Design: Custom-designed (Canva, Figma)
- Include: ÅbenForms logo, video title, episode number
- Text: Large, readable even at small size
- Avoid: Clickbait, misleading images

**Playlist:**
- Create playlist: "ÅbenForms Demo Series"
- Add all 5 videos in order
- Playlist description: Same as channel description

**Chapters:**
- Add in description with timestamps
- YouTube auto-creates chapters from description

**End Screen:**
- Add at last 20 seconds
- Elements:
  - Subscribe button
  - Next video in series
  - Playlist link
  - Website link

**Cards:**
- Add cards throughout video
- Link to related videos
- Link to website at key moments

**Visibility:**
- Start as "Unlisted" for internal review
- After approval, set to "Public"
- Schedule premiere (optional, for marketing)

### Website Embed

**Video Hosting:**
- Option 1: Embed YouTube video (easy, free, but YouTube branding)
- Option 2: Self-host on aabenforms.dk (control, no branding, but bandwidth cost)
- Option 3: Vimeo Pro ($20/month, professional, customizable)

**Embedding Code (YouTube):**
```html
<iframe width="1280" height="720"
  src="https://www.youtube.com/embed/[VIDEO_ID]?rel=0"
  title="ÅbenForms: Platform Overview"
  frameborder="0"
  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
  allowfullscreen>
</iframe>
```

**Video Page Structure:**
```
/videos/
  /platform-overview
  /parking-permit-demo
  /marriage-booking-demo
  /building-permit-demo
  /visual-builder-tutorial
```

Each page includes:
- Embedded video player
- Video title and description
- Download link (PDF script, workflow templates)
- Related resources
- Next/previous video navigation
- Transcript (expandable section)

### Social Media

**LinkedIn:**
- Post as native video (better reach than YouTube link)
- Caption format:
  ```
  🚀 [Compelling hook question or statement]

  [2-3 sentences explaining value]

  Key takeaways:
  ✓ [Benefit 1]
  ✓ [Benefit 2]
  ✓ [Benefit 3]

  Watch full video: [link]

  #ÅbenForms #KommunalDigitalisering #OpenSource #Danmark
  ```

**Twitter/X:**
- 1-2 minute excerpt (Twitter supports up to 2:20)
- Thread format:
  ```
  1/ New video: [Title] 🎥

  [One sentence hook]

  2/ [Key insight screenshot]
  [Brief explanation]

  3/ Watch full video: [link]

  #ÅbenForms #OpenSource
  ```

**Facebook:**
- Post to ÅbenForms page
- Native video upload (better reach)
- Caption similar to LinkedIn but more casual tone
- Tag relevant municipal organizations

**Instagram:**
- Reels: 30-60 second vertical excerpt (1080x1920)
- Feed: Square version (1080x1080), 45 seconds
- Stories: Vertical, multiple slides with swipe-up link
- Subtitles: Burned in (most view without sound)

---

## Quality Assurance Checklist

Before publishing any video, verify:

### Technical Quality
- [ ] Video resolution is 1920x1080 (1080p)
- [ ] Frame rate is consistent 30fps (no drops)
- [ ] Audio levels balanced (-3dB narration, -18dB music)
- [ ] No audio pops, clicks, or background noise
- [ ] Subtitles synced correctly to narration
- [ ] All graphics readable at 1080p
- [ ] No typos in text overlays or graphics
- [ ] Transitions smooth and professional

### Content Quality
- [ ] Narration follows script (or approved deviations)
- [ ] All screen actions demonstrated clearly
- [ ] Timing matches script estimates (±10%)
- [ ] All visual callouts present per script
- [ ] Demo environment works correctly (no errors)
- [ ] All integrations demonstrated successfully
- [ ] Branding consistent (colors, fonts, logos)

### Accessibility
- [ ] Danish subtitles complete and accurate
- [ ] English subtitles complete and accurate (if separate version)
- [ ] High contrast visuals (readable for colorblind)
- [ ] Text readable at small sizes (mobile)
- [ ] Full transcript in video description

### Publishing
- [ ] YouTube title, description, tags optimized
- [ ] Custom thumbnail designed and uploaded
- [ ] Chapters added to description
- [ ] End screen configured (subscribe, next video)
- [ ] Video added to playlist
- [ ] Visibility set correctly (public/unlisted)
- [ ] Website embed tested and working
- [ ] Social media versions created

### Legal
- [ ] Background music royalty-free or licensed
- [ ] All logos used with permission
- [ ] No copyrighted content without permission
- [ ] Personal data anonymized (if using real data)
- [ ] GDPR compliant (if showing user data)

---

## Troubleshooting Common Issues

### Recording Issues

**Problem: Choppy/laggy screen recording**
- Solution: Lower recording resolution to 720p, or reduce recording frame rate to 24fps
- Solution: Close all background applications
- Solution: Use GPU encoding (NVENC) instead of CPU (x264)
- Solution: Record to SSD, not HDD

**Problem: Audio echo or reverb**
- Solution: Record in smaller room with soft furnishings
- Solution: Add acoustic treatment (blankets, foam)
- Solution: Use directional microphone closer to mouth
- Solution: Apply noise reduction in Audacity

**Problem: Background noise (traffic, HVAC)**
- Solution: Record late night/early morning
- Solution: Turn off HVAC during recording
- Solution: Use noise reduction in post (Audacity, Adobe Audition)
- Solution: Re-record affected sections

**Problem: Demo environment errors during recording**
- Solution: Always test environment before recording
- Solution: Have backup test accounts ready
- Solution: Keep takes short, easier to re-record if needed
- Solution: Pause recording, fix issue, resume (fix in editing)

### Editing Issues

**Problem: Audio and video out of sync**
- Solution: Use constant frame rate recording (not variable)
- Solution: Sync using audio waveform markers
- Solution: Re-export with "constant frame rate" enabled

**Problem: Colors look different on different displays**
- Solution: Use color calibration tool (DisplayCAL)
- Solution: Edit on calibrated monitor
- Solution: Export with sRGB color space (standard)
- Solution: Test on multiple devices before publishing

**Problem: File size too large**
- Solution: Use 2-pass VBR encoding (better compression)
- Solution: Reduce bitrate to 8,000-10,000 Kbps (still good quality)
- Solution: Use H.265 codec (smaller files, but slower encoding)
- Solution: Trim unnecessary sections

**Problem: Graphics/text blurry**
- Solution: Create graphics at 2x resolution, scale down
- Solution: Ensure editor is set to 1920x1080 (not scaled)
- Solution: Use PNG for graphics (not JPG)
- Solution: Check "sharpen" filter is not over-applied

### Publishing Issues

**Problem: YouTube processing stuck**
- Solution: Wait 1-2 hours (large files take time)
- Solution: Re-upload if stuck > 4 hours
- Solution: Upload during off-peak hours

**Problem: Subtitles not displaying**
- Solution: Check SRT file format (UTF-8 encoding, proper format)
- Solution: Re-upload subtitle file
- Solution: Test with different video player

**Problem: Video blocked in some countries**
- Solution: Check music licensing (some tracks restricted)
- Solution: Replace music with different track
- Solution: Use YouTube Audio Library only (always cleared)

---

## Performance Metrics

Track these metrics for each video:

### YouTube Analytics

**Engagement:**
- Views (first 24h, first week, total)
- Watch time (total hours)
- Average view duration (target: 60%+ retention)
- Click-through rate on thumbnail (target: 4-10%)
- Likes/dislikes ratio
- Comments count and sentiment

**Audience:**
- Demographics (age, gender, location)
- Traffic sources (search, suggested, external)
- Devices (mobile vs desktop)
- Subscribers gained/lost

**Discovery:**
- Impressions
- Search terms leading to video
- Suggested video performance

### Website Analytics (Google Analytics)

- Page views on video pages
- Time on page
- Video play rate (% who click play)
- Video completion rate
- Conversions (demo signups, downloads)

### Social Media

- Shares, retweets, reposts
- Comments and engagement rate
- Click-throughs to main video
- Follower growth

### Target Benchmarks (First Month)

- **Platform Overview**: 1,000+ views, 65%+ retention
- **Parking Permit**: 500+ views, 55%+ retention
- **Marriage Booking**: 400+ views, 55%+ retention
- **Building Permit**: 600+ views, 60%+ retention (key differentiator)
- **Visual Builder**: 800+ views, 50%+ retention (longer video)

---

## Budget Estimate

### Minimal Budget (DIY)

**Equipment:**
- USB Microphone: $50-100 (Blue Yeti, Audio-Technica AT2020USB+)
- Headphones: $30-50 (for monitoring)
- **Total**: ~$100

**Software:**
- OBS Studio: FREE
- DaVinci Resolve: FREE
- Audacity: FREE
- Canva Free: FREE
- **Total**: $0

**Time:**
- Recording (5 videos × 3 hours): 15 hours
- Editing (5 videos × 6 hours): 30 hours
- Graphics (5 videos × 2 hours): 10 hours
- **Total**: ~55 hours

**Grand Total**: ~$100 + 55 hours labor

### Professional Budget

**Equipment:**
- Audio Interface: $150 (Focusrite Scarlett 2i2)
- Studio Microphone: $200 (Rode NT1-A)
- Headphones: $150 (Audio-Technica ATH-M50x)
- Camera (if PIP): $300 (Logitech Brio or used DSLR)
- Lighting: $100 (ring light or softbox)
- **Total**: ~$900

**Software:**
- Adobe Creative Cloud (Premiere, After Effects, Audition): $55/month
- Epidemic Sound (music): $15/month
- Rev.com (subtitles, 5 videos × 8 min avg × $1.50/min): ~$60
- **Total**: ~$130/month + $60 one-time

**Outsourcing (optional):**
- Professional voice-over artist (Danish): $100-200 per video
- Video editor: $500-1000 per video
- Graphic designer: $200-400 per video
- **Total**: $800-1600 per video × 5 = $4,000-8,000

**Time (if done in-house):**
- Recording: 15 hours
- Editing: 40 hours (more polished)
- Graphics: 15 hours
- **Total**: ~70 hours

**Grand Total (DIY with pro equipment)**: ~$1,000 + $130/month + 70 hours
**Grand Total (fully outsourced)**: ~$5,000-10,000

---

## Timeline Estimate

### Single Video (Start to Finish)

- **Day 1-2**: Script review, environment setup, dry runs
- **Day 3**: Recording (all segments, multiple takes)
- **Day 4-5**: Rough edit, audio post-production
- **Day 6-7**: Graphics creation, visual enhancements
- **Day 8**: Subtitles, quality control
- **Day 9**: Revisions based on feedback
- **Day 10**: Export, upload, publish

**Total**: ~10 working days per video (if working alone)

### Full Series (5 Videos)

**Option 1: Sequential** (one video at a time)
- 10 days × 5 videos = 50 working days (~10 weeks)

**Option 2: Parallel** (overlap phases)
- Record all 5 videos: Week 1-2 (10 days)
- Edit all 5 videos: Week 3-5 (15 days)
- Graphics all 5 videos: Week 6 (5 days)
- QA & publish all 5: Week 7 (5 days)
- **Total**: ~7 weeks

**Option 3: Staggered Release**
- Produce 1 video per 2 weeks
- Publish 1 video per 2 weeks
- **Total**: 10 weeks, but continuous production

---

## Support & Resources

### Learning Resources

**Screen Recording:**
- OBS Studio Guide: https://obsproject.com/wiki/
- Camtasia Tutorials: https://www.techsmith.com/learn/

**Video Editing:**
- DaVinci Resolve Training: https://www.blackmagicdesign.com/products/davinciresolve/training
- Premiere Pro Tutorials: https://helpx.adobe.com/premiere-pro/tutorials.html

**Audio:**
- Booth Junkie (YouTube): Microphone reviews, audio tips
- Mike Russell (YouTube): Music Radio Creative podcast

**Graphics:**
- Canva Design School: https://www.canva.com/designschool/
- Figma Tutorial: https://www.figma.com/resources/learn-design/

### Community

- ÅbenForms Community Forum: community.aabenforms.dk
- GitHub Discussions: github.com/aabenforms/platform/discussions
- Discord: [create ÅbenForms Discord server]

### Contact

For questions about video production:
- Email: video@aabenforms.dk
- Slack: #video-production channel

---

## License

These video scripts and production guides are licensed under:
- **Scripts**: CC BY-SA 4.0 (Creative Commons Attribution-ShareAlike)
- **Videos**: CC BY-SA 4.0 (when published)
- **Code examples**: AGPL-3.0 (same as ÅbenForms platform)

You are free to:
- Use these scripts for your own municipality
- Modify and adapt for your needs
- Share with other municipalities
- Create derivative works

You must:
- Give appropriate credit to ÅbenForms
- Share modifications under the same license
- Indicate if changes were made

---

**Last updated**: 2024-02-02
**Version**: 1.0.0
**Maintained by**: ÅbenForms Core Team
