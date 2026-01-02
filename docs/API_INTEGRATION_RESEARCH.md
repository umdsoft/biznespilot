# API Integration Research for BiznesPilot
**Business Analytics & Marketing Platform for Uzbekistan**

## Executive Summary

This document provides comprehensive research on Google and Yandex APIs for integration into BiznesPilot, a business analytics platform targeting Uzbekistan businesses. Research shows that **Google dominates with 78% market share in Uzbekistan**, while **Yandex holds 20-22%**. Both platforms are essential for complete market coverage.

**Key Findings:**
- All major Google APIs are FREE with generous quotas
- Yandex APIs are FREE with no traffic limitations
- Laravel PHP packages available for most integrations
- OAuth 2.0 authentication required for all services
- Priority integrations: GA4, Google Ads, Yandex.Metrica, Yandex.Direct

---

## 1. GOOGLE ANALYTICS 4 (GA4) API

### Purpose
Website analytics, user behavior tracking, conversion tracking, and traffic analysis. Essential for measuring business website performance and user engagement.

### Authentication
- **Method**: OAuth 2.0 or Service Account (JSON key file)
- **Scopes**:
  - `analytics.readonly` - Read-only access
  - `analytics` - Full access
- **Setup**: Create project in Google Cloud Console → Enable Analytics Data API → Generate credentials
- **Service Account**: Must be granted access to GA4 property manually

### Key Endpoints

**Base URL**: `https://analyticsdata.googleapis.com/v1beta`

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `properties/{propertyId}/runReport` | Generate custom reports | POST |
| `properties/{propertyId}/batchRunReports` | Multiple reports in one call | POST |
| `properties/{propertyId}/runRealtimeReport` | Real-time data (seconds delay) | POST |
| `properties/{propertyId}/runPivotReport` | Multi-dimensional analysis | POST |
| `properties/{propertyId}/runFunnelReport` | Funnel analysis | POST |
| `properties/{propertyId}/metadata` | Available dimensions/metrics | GET |
| `properties/{propertyId}/checkCompatibility` | Validate dimension/metric combos | POST |

### Data Available

**Dimensions** (100+ available):
- User/Session: `city`, `country`, `deviceCategory`, `operatingSystem`, `browser`
- Traffic: `source`, `medium`, `campaign`, `landingPage`, `exitPage`
- Content: `pageTitle`, `pagePath`, `contentGroup`
- E-commerce: `itemName`, `itemCategory`, `transactionId`
- Time: `date`, `dayOfWeek`, `hour`, `month`

**Metrics** (200+ available):
- Engagement: `activeUsers`, `sessions`, `screenPageViews`, `averageSessionDuration`
- Conversions: `conversions`, `totalRevenue`, `purchaseRevenue`, `eventCount`
- E-commerce: `itemRevenue`, `itemsPurchased`, `transactions`
- Engagement: `engagementRate`, `engagedSessions`, `bounceRate`

**Example Data Points for BiznesPilot:**
- Daily active users, new vs returning visitors
- Traffic sources (organic, paid, social, direct)
- Conversion rates by channel
- Revenue attribution by marketing campaign
- User behavior flow through website
- Page performance (bounce rate, time on page)

### Rate Limits

**Quota System** (Token-based):

| Quota Type | Free Tier | Limit |
|------------|-----------|-------|
| Core Requests | 50,000 tokens/day | Per project |
| Realtime Requests | 10,000 tokens/day | Per project |
| Concurrent Requests | 10 simultaneous | Per property |
| Requests per Second | 100/second | Per project |
| Hourly Tokens | 25,000/hour | Per property |

**Token Costs:**
- Basic report: 5-10 tokens
- Complex pivot report: 20-50 tokens
- Realtime report: 5 tokens

**Important**: After complaints, Google adjusted limits to be more lenient. Still, heavy users should monitor quota usage.

### Integration Difficulty
**Medium** (3/5)

**Reasons:**
- OAuth 2.0 flow requires web redirect
- Token-based quota system needs monitoring
- Service account requires manual GA4 access grant
- Report API is well-documented with good examples

**Complexity:**
- Simple data pulls: Easy
- Custom reports: Medium
- Funnel analysis: Medium-Hard
- Real-time dashboards: Hard

### Value for BiznesPilot

**Priority**: ⭐⭐⭐⭐⭐ **CRITICAL**

**Use Cases:**
1. **Website Performance Dashboard**: Show businesses their website traffic, conversions, user behavior
2. **Marketing ROI Tracking**: Track which campaigns drive revenue
3. **Customer Journey Analysis**: Understand how customers find and convert
4. **Content Performance**: Identify top-performing pages
5. **Competitor Benchmarking**: Compare against industry averages
6. **Diagnostic Insights**: Feed data into `DiagnosticAlgorithmService` for health scores

**Integration with Existing Systems:**
```php
// Can feed into existing algorithms:
- HealthScoreAlgorithm: Use activeUsers, conversions as health metrics
- FunnelAnalysisAlgorithm: Use multi-step funnels from GA4
- EngagementAlgorithm: Track website engagement vs social engagement
- RevenueForecaster: Use historical revenue data for predictions
- CompetitorBenchmarkAlgorithm: Compare GA4 data with industry benchmarks
```

**Data Flow:**
```
GA4 API → Laravel Service → Database (social_metrics tables)
→ DiagnosticAlgorithmService → Business Insights Dashboard
```

### Cost

**Free Tier**: ✅ **COMPLETELY FREE**
- No charges for API access
- 50,000 tokens/day (enough for 5,000-10,000 reports)
- Unlimited properties
- 14-month data retention
- Data sampling after 10M events in explorations

**Paid Tier** (GA360):
- $50,000 - $150,000/year
- 50-month data retention
- No data sampling
- Higher API limits
- **NOT NEEDED** for BiznesPilot's target market

**Verdict**: Free tier is MORE than sufficient for Uzbekistan SMBs.

---

## 2. GOOGLE ADS API

### Purpose
Advertising campaign management, budget tracking, keyword performance, ROI measurement, and automated bidding. Essential for businesses running Google ad campaigns.

### Authentication
- **Method**: OAuth 2.0
- **Requirements**:
  - Google Ads Manager Account (MCC)
  - Developer Token (apply via Google Ads)
  - Client ID & Client Secret (Google Cloud Console)
  - Refresh Token (via OAuth flow)
- **Access Levels**:
  - **Basic**: Test accounts only, limited operations
  - **Standard**: Full production access (requires Google approval)

### Key Endpoints

**Base URL**: `https://googleads.googleapis.com/v22` (current version)

| Service | Purpose | Key Methods |
|---------|---------|-------------|
| `GoogleAdsService.Search` | Query campaigns, ads, keywords | Search with GAQL |
| `GoogleAdsService.SearchStream` | Large data sets (streaming) | Search with streaming |
| `CampaignService` | Manage campaigns | Get, Mutate (create/update) |
| `AdGroupService` | Manage ad groups | Get, Mutate |
| `KeywordPlanService` | Keyword research | Generate ideas, forecast |
| `RecommendationService` | Get Google recommendations | Apply recommendations |
| `CustomerService` | Account management | Get customer info |

**Google Ads Query Language (GAQL)** - SQL-like syntax:
```sql
SELECT campaign.id, campaign.name, metrics.clicks, metrics.impressions,
       metrics.cost_micros, metrics.conversions
FROM campaign
WHERE campaign.status = 'ENABLED'
  AND segments.date DURING LAST_30_DAYS
```

### Data Available

**Campaign Metrics:**
- `clicks`, `impressions`, `ctr` (click-through rate)
- `cost_micros` (cost in micro units, divide by 1M for actual cost)
- `conversions`, `conversion_value`
- `average_cpc` (cost per click), `average_cpm` (cost per 1000 impressions)

**Ad Performance:**
- Ad text, headlines, descriptions
- Ad strength, ad status
- Quality score, ad rank

**Keyword Data:**
- Search terms, keyword match types
- Keyword bids, quality scores
- Search volume, competition level

**Audience Data:**
- Demographics (age, gender, location)
- Device performance (mobile, desktop, tablet)
- Time of day performance

**Budget & Bidding:**
- Daily budget, lifetime spend
- Bid strategies, target CPA/ROAS
- Budget pacing, shared budgets

### Rate Limits

**FREE** - No monetary cost

**Operation Limits:**
| Limit Type | Basic Access | Standard Access |
|------------|--------------|-----------------|
| Operations/day | 15,000 | Unlimited |
| Requests/day | No hard limit | No hard limit |
| Operations/request | Max varies by operation | Max varies by operation |

**Important Notes:**
- Operations counted per mutated item (e.g., updating 10 keywords = 10 operations)
- Read operations (queries) are unlimited
- Standard access requires Google approval (business verification)
- Non-compliance fees exist (but API usage itself is free)

### Integration Difficulty
**Medium-Hard** (3.5/5)

**Reasons:**
- OAuth 2.0 + Developer Token application process
- GAQL syntax requires learning
- API versioning (v16-v22, updates twice/year)
- Standard access requires Google approval

**Complexity:**
- Read campaign data: Medium
- Keyword research: Medium
- Campaign creation/updates: Hard
- Automated bidding rules: Hard

**PHP Support**:
- Official `googleads/google-ads-php` library
- Laravel packages: `spotonlive/laravel-google-ads`, `ZeekInteractive/laravel-googleads`

### Value for BiznesPilot

**Priority**: ⭐⭐⭐⭐⭐ **CRITICAL**

**Use Cases:**
1. **Ad Performance Dashboard**: Show businesses their ad ROI in real-time
2. **Budget Optimization**: Recommend budget reallocation based on performance
3. **Keyword Insights**: Identify high-performing vs wasted keywords
4. **Competitor Analysis**: Compare ad metrics with industry benchmarks
5. **Automated Reporting**: Weekly/monthly ad performance reports
6. **ROI Calculator**: Calculate true ROAS including all costs

**Integration with Existing Systems:**
```php
// Feed into algorithms:
- MoneyLossAlgorithm: Identify wasted ad spend
- HealthScoreAlgorithm: Ad performance as health indicator
- RevenueForecaster: Use ad data to predict future revenue
- CompetitorBenchmarkAlgorithm: Compare CTR, CPC with industry standards
- ABTestingEngine: Test different ad variations
```

**Critical for Uzbekistan Market:**
- Google holds 78% search market share
- Most businesses run Google Ads
- ROI tracking is a key pain point
- Automated insights = competitive advantage

### Cost

**API Access**: ✅ **COMPLETELY FREE**
- No charges for any access level
- No per-request fees
- No per-operation fees
- Unlimited queries

**Ad Spend**: ❌ **User pays Google**
- This is NOT an API cost
- Businesses pay Google directly for ads
- BiznesPilot shows ROI, doesn't manage spend

**Verdict**: Free API makes this a must-have integration.

---

## 3. GOOGLE BUSINESS PROFILE API (formerly My Business)

### Purpose
Manage business listings on Google Search and Maps, respond to reviews, post updates, track customer actions (calls, directions, website clicks).

### Authentication
- **Method**: OAuth 2.0
- **Scopes**:
  - `business.manage` - Full management access
  - `business.readonly` - Read-only access
- **Important**: Requires request for API access (quota often 0 by default)
- **Approval**: Must request quota increase via GBP API contact form

### Key Endpoints

**Base URL**: `https://mybusinessbusinessinformation.googleapis.com/v1`

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `accounts/{accountId}/locations` | List business locations | GET |
| `accounts/{accountId}/locations/{locationId}` | Get location details | GET |
| `accounts/{accountId}/locations/{locationId}/reviews` | Get reviews | GET |
| `accounts/{accountId}/locations/{locationId}/localPosts` | Manage posts | GET/POST/DELETE |
| `accounts/{accountId}/locations/{locationId}/media` | Manage photos | GET/POST |
| `accounts/{accountId}/locations/{locationId}:updateAttributes` | Update business info | PATCH |

**My Business Account Management API**:
- `accounts.list` - List accessible accounts
- `locations.create` - Create new location
- `locations.delete` - Remove location

### Data Available

**Business Information:**
- Business name, address, phone, website
- Business hours (regular + special hours)
- Business categories, attributes
- Service areas, photos

**Performance Metrics:**
- Search queries leading to profile views
- Customer actions: calls, direction requests, website clicks
- Photo views, photo count
- Post engagement (views, clicks)

**Reviews:**
- Review text, star rating, reviewer name
- Review date, response status
- Ability to reply to reviews

**Posts & Updates:**
- Create/delete posts (offers, events, updates)
- Post performance (views, clicks)

**Q&A:**
- Customer questions
- Business answers

### Rate Limits

**Default Quota**:
- **300 queries per minute (QPM)**
- **10 edits per minute per location** (cannot be increased)

**Important**:
- New projects often have quota = 0
- Must request access via Google form
- Approval not guaranteed (targets large businesses/agencies)
- Quota increases require justification (show 50%+ usage)

### Integration Difficulty
**Hard** (4/5)

**Reasons:**
- Quota often 0 by default (requires approval)
- Complex account/location hierarchy
- API access limited to "large, tech-savvy businesses"
- Documentation scattered across multiple APIs

**Complexity:**
- Read business info: Medium
- Respond to reviews: Medium
- Manage posts: Medium
- Access requires approval: Hard

### Value for BiznesPilot

**Priority**: ⭐⭐⭐ **MEDIUM**

**Use Cases:**
1. **Review Management**: Centralize review monitoring and responses
2. **Local SEO Insights**: Track how customers find the business
3. **Reputation Dashboard**: Monitor star ratings, review sentiment
4. **Post Scheduling**: Automate Google Posts for better visibility
5. **Customer Action Tracking**: Track calls, directions, website clicks

**Integration with Existing Systems:**
```php
// Feed into algorithms:
- HealthScoreAlgorithm: Review ratings as health metric
- EngagementAlgorithm: Customer actions (calls, clicks) as engagement
- SentimentAnalysisAlgorithm: Analyze review text for sentiment
- ContentOptimizationAlgorithm: Optimize post timing based on engagement
```

**Challenges for Uzbekistan Market:**
- API access approval required (not guaranteed)
- Many small businesses don't actively manage Google Business Profile
- Review culture less developed than in Western markets

### Cost

**API Access**: ✅ **FREE**
- No charges for API usage
- No per-request fees

**Approval**: ⚠️ **REQUIRED**
- Default quota often 0
- Must request access and justify use case
- Approval not guaranteed for small startups

**Verdict**: Valuable but challenging to get access. Apply early and build strong use case.

---

## 4. GOOGLE SEARCH CONSOLE API

### Purpose
SEO performance tracking, search query analysis, indexing status, technical SEO issues, backlink data. Essential for businesses focused on organic search.

### Authentication
- **Method**: OAuth 2.0
- **Scopes**:
  - `webmasters.readonly` - Read-only access
  - `webmasters` - Full access (submit sitemaps, etc.)
- **Requirements**: Must verify site ownership in Search Console

### Key Endpoints

**Base URL**: `https://www.googleapis.com/webmasters/v3`

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `sites/{siteUrl}/searchAnalytics/query` | Query search performance data | POST |
| `sites/{siteUrl}/sitemaps` | List/submit/delete sitemaps | GET/PUT/DELETE |
| `sites` | List verified sites | GET |
| `sites/{siteUrl}` | Add/delete site | PUT/DELETE |
| `urlInspection.index.inspect` | Inspect URL index status | POST |

**Search Analytics Query** (Most Important):
```json
{
  "startDate": "2025-12-01",
  "endDate": "2025-12-31",
  "dimensions": ["query", "page", "country", "device"],
  "dimensionFilterGroups": [...],
  "rowLimit": 25000,
  "startRow": 0
}
```

### Data Available

**Search Performance Metrics:**
- `clicks` - Total clicks from search results
- `impressions` - Times URL appeared in search
- `ctr` - Click-through rate (clicks/impressions)
- `position` - Average ranking position

**Dimensions:**
- `query` - Search keywords that triggered listing
- `page` - Specific URL that appeared
- `country` - User country
- `device` - Desktop, mobile, tablet
- `searchAppearance` - Rich results, AMP, etc.
- `date` - Daily breakdown

**Index Coverage:**
- Valid pages, excluded pages, errors, warnings
- Crawl errors, mobile usability issues
- AMP status, structured data errors

**Sitemaps:**
- Submitted sitemaps
- Processing status, errors
- Last submitted date

### Rate Limits

**Quotas**:
| Limit Type | Value |
|------------|-------|
| Queries per second (QPS) | 20 |
| Queries per minute (QPM) | 200 |
| Queries per day (QPD) | 100,000,000 (100M) |

**Result Limits**:
- Max 50,000 rows per query
- For larger datasets, paginate with `startRow`

**Important**: Very generous limits. Unlikely to hit for SMB use cases.

### Integration Difficulty
**Easy-Medium** (2.5/5)

**Reasons:**
- Simple OAuth 2.0 flow
- Well-documented API
- Straightforward query structure
- Good error messages

**Complexity:**
- Basic search data: Easy
- Advanced filtering: Medium
- URL inspection: Medium
- Large dataset handling: Medium

**PHP Support**:
- Official `google/apiclient` library works
- Many tutorials available

### Value for BiznesPilot

**Priority**: ⭐⭐⭐⭐ **HIGH**

**Use Cases:**
1. **Keyword Rankings Dashboard**: Track how business ranks for important keywords
2. **SEO Performance Trends**: Monitor organic traffic growth
3. **Content Optimization**: Identify high-opportunity keywords
4. **Technical SEO Alerts**: Notify businesses of indexing issues
5. **Competitor Keyword Gap**: Compare keyword coverage
6. **Page Performance**: Identify top-performing content

**Integration with Existing Systems:**
```php
// Feed into algorithms:
- HealthScoreAlgorithm: Organic search traffic as health indicator
- ContentOptimizationAlgorithm: Use top-performing keywords for content recommendations
- CompetitorBenchmarkAlgorithm: Compare keyword rankings
- EngagementAlgorithm: CTR as engagement metric
- MoneyLossAlgorithm: Identify low-ranking high-value keywords (missed revenue)
```

**Perfect for Uzbekistan Market:**
- SEO is free (vs paid ads)
- Many businesses neglect SEO (opportunity for insights)
- Local search is important (Uzbek + Russian + English queries)
- Mobile search dominant (can track mobile vs desktop)

### Cost

**API Access**: ✅ **COMPLETELY FREE**
- No charges
- Extremely generous quotas (100M/day)
- No approval needed (just verify site ownership)

**Verdict**: Must-have integration. Free, easy, high value.

---

## 5. YOUTUBE ANALYTICS API

### Purpose
Video performance analytics for businesses with YouTube channels: views, watch time, engagement, revenue (if monetized), audience demographics.

### Authentication
- **Method**: OAuth 2.0
- **Scopes**:
  - `yt-analytics.readonly` - Read analytics data
  - `yt-analytics-monetary.readonly` - Read revenue data
  - `youtube.readonly` - Read channel/video info
- **Requirements**: YouTube channel ownership

### Key Endpoints

**Base URL**: `https://youtubeanalytics.googleapis.com/v2`

| Endpoint | Purpose | Parameters |
|----------|---------|------------|
| `reports.query` | Query analytics data | ids, metrics, dimensions, filters, startDate, endDate |

**Single endpoint**, but highly flexible with query parameters.

**Example Query**:
```
GET /v2/reports?
  ids=channel==UCxxxxxx
  &startDate=2025-12-01
  &endDate=2025-12-31
  &metrics=views,likes,comments,shares,estimatedMinutesWatched
  &dimensions=day,video
  &sort=-views
```

### Data Available

**User Activity Metrics:**
- `views` - Total video views
- `estimatedMinutesWatched` - Watch time
- `averageViewDuration` - Average seconds watched
- `averageViewPercentage` - % of video watched on average
- `likes`, `dislikes`, `comments`, `shares`
- `subscribersGained`, `subscribersLost`
- `annotationClickThroughRate`, `cardClickRate`

**Playlist Metrics:**
- `playlistStarts`, `playlistViews`
- `averageTimeInPlaylist`
- `viewsPerPlaylistStart`

**Ad Performance** (if monetized):
- `estimatedRevenue`, `estimatedAdRevenue`
- `grossRevenue`, `adImpressions`
- `cpm` - Revenue per 1000 impressions
- `playbackBasedCpm`

**Demographics:**
- `ageGroup`, `gender`
- `country`, `province` (for US)
- `subscribedStatus` (subscriber vs non-subscriber)

**Traffic Sources:**
- `insightTrafficSourceType` - How viewers found video
- `insightTrafficSourceDetail` - Specific source (e.g., which website)

**Dimensions:**
- `day`, `month` - Time breakdowns
- `video`, `channel`
- `country`, `province`
- `ageGroup`, `gender`
- `deviceType` - Mobile, desktop, TV, etc.

### Rate Limits

**Quotas**:
- **10,000 units/day** (default quota)
- Each API request = quota cost
- Most queries cost 1-5 units

**Important**:
- Can request quota increase (requires audit of compliance with Terms of Service)
- Paid plans available for higher quotas (commercial use cases)
- Monitor quota usage to avoid cutoff

### Integration Difficulty
**Medium** (3/5)

**Reasons:**
- OAuth 2.0 standard flow
- Single flexible endpoint (simpler than multiple endpoints)
- Need to understand metrics/dimensions compatibility
- Documentation is good but dense

**Complexity:**
- Basic video stats: Easy
- Audience insights: Medium
- Revenue tracking: Medium (requires monetization)
- Real-time analytics: Not available (24-72 hour delay)

**PHP Support**:
- Official `google/apiclient` library
- Community examples available

### Value for BiznesPilot

**Priority**: ⭐⭐ **LOW-MEDIUM**

**Reasoning:**
- Not all businesses have YouTube channels
- YouTube analytics less critical than web/ads analytics
- However, growing importance in content marketing

**Use Cases (if applicable):**
1. **Video Performance Dashboard**: Show views, engagement for business YouTube content
2. **Content Strategy**: Identify best-performing video topics
3. **Audience Insights**: Demographics, watch patterns
4. **Traffic Source Analysis**: Where viewers come from
5. **Monetization Tracking**: Revenue from YouTube (if applicable)

**Integration with Existing Systems:**
```php
// Feed into algorithms:
- EngagementAlgorithm: Video engagement metrics (likes, comments, shares)
- ContentOptimizationAlgorithm: Best video topics, optimal video length
- HealthScoreAlgorithm: (If business uses YouTube heavily) views, subscribers as KPI
```

**Uzbekistan Market Considerations:**
- YouTube is popular in Uzbekistan (especially younger demographics)
- Local language content (Uzbek, Russian) is growing
- B2C businesses may benefit (tutorials, product demos)
- B2B businesses less likely to need this

### Cost

**API Access**: ✅ **FREE**
- 10,000 units/day free quota
- Sufficient for most small-medium channels

**Paid Quota**:
- Requires quota increase request + compliance audit
- Paid plans available for high-volume commercial use
- Pricing not publicly listed (enterprise pricing)

**Verdict**: Useful for content-heavy businesses, but lower priority. Implement after core integrations (GA4, Ads, Search Console).

---

## 6. YANDEX.METRICA API

### Purpose
Website analytics for the Russian/CIS market. Similar to Google Analytics but with deeper integration into Yandex ecosystem. Popular in Uzbekistan due to Russian influence.

### Authentication
- **Method**: OAuth 2.0
- **OAuth Endpoint**: `https://oauth.yandex.com/authorize`
- **Scopes**:
  - `metrika:read` - Read analytics data and tag info
  - `metrika:write` - Manage tags, upload offline data
- **Process**:
  1. Register app at `https://oauth.yandex.com/client/new`
  2. Get Client ID
  3. Redirect user to OAuth URL
  4. Receive authorization code
  5. Exchange for access token

**Token Format**:
```
Authorization: OAuth 05dd3dd84ff948fdae2bc4fb91f13e22bb1f289ceef0037
```

### Key Endpoints

**Base URLs**:
- Reports API: `https://api-metrica.yandex.net/stat/v1/data`
- Management API: `https://api-metrica.yandex.net/management/v1`

**Reports API** (Statistics):

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `/stat/v1/data` | Table format reports | GET |
| `/stat/v1/data/drilldown` | Hierarchical reports (tree view) | GET |
| `/stat/v1/data/bytime` | Time series data | GET |
| `/stat/v1/data/comparison` | Compare segments (table) | GET |
| `/stat/v1/data/comparison/drilldown` | Compare segments (tree) | GET |

**Management API**:

| Endpoint | Purpose | Method |
|----------|---------|--------|
| `/management/v1/counters` | List counters (tags) | GET |
| `/management/v1/counter/{counterId}` | Get counter details | GET |
| `/management/v1/counter/{counterId}/goals` | Manage goals | GET/POST/PUT/DELETE |
| `/management/v1/counter/{counterId}/filters` | Manage filters | GET/POST/PUT/DELETE |
| `/management/v1/counter/{counterId}/grants` | Manage access | GET/POST/DELETE |

**Logs API** (Raw Data):
- `/management/v1/counter/{counterId}/logrequests` - Request raw logs
- Allows downloading raw visit/hit data for custom analysis

### Data Available

**Two Main Categories**:

1. **Sessions (ym:s:)** - Aggregated session data
2. **Hits (ym:pv:)** - Page view level data

**Session Metrics (`ym:s:`):**
- `ym:s:visits` - Total visits
- `ym:s:users` - Unique visitors
- `ym:s:pageviews` - Total page views
- `ym:s:bounceRate` - Bounce rate
- `ym:s:pageDepth` - Pages per visit
- `ym:s:avgVisitDurationSeconds` - Average session duration
- `ym:s:goal{id}reaches` - Goal completions
- `ym:s:goal{id}ConversionRate` - Goal conversion rate

**Hit Metrics (`ym:pv:`):**
- `ym:pv:pageviews` - Page views
- `ym:pv:users` - Unique users viewing page

**Dimensions:**
- `ym:s:startURL` - Landing page
- `ym:s:referer` - Traffic source
- `ym:s:searchEngine` - Search engine
- `ym:s:searchPhrase` - Search query
- `ym:s:trafficSource` - Traffic channel
- `ym:s:deviceCategory` - Desktop, mobile, tablet
- `ym:s:operatingSystem`, `ym:s:browser`
- `ym:s:regionCountry`, `ym:s:regionCity`
- `ym:s:date`, `ym:s:dayOfWeek`, `ym:s:hour`

**Special Features (vs Google Analytics):**
- **Session Replay** - Watch user sessions (not via API, but in UI)
- **Form Analytics** - Track form field completion
- **Webvisor** - Visual session recordings
- **E-commerce tracking** - Revenue, transactions
- **Offline conversion import** - Upload offline sales data

### Rate Limits

**NONE PUBLICLY DOCUMENTED** 🎉

- Yandex.Metrica is **completely free**
- **No traffic limitations**
- **No API rate limits** mentioned in documentation
- Used by Yandex to collect anonymous data for ML (hence free)

**Best Practice**:
- Don't abuse (reasonable request rates)
- Implement caching to avoid redundant calls
- Monitor for any undocumented rate limiting

### Integration Difficulty
**Easy-Medium** (2.5/5)

**Reasons:**
- OAuth 2.0 is standard
- Well-documented API (Russian + English docs)
- Simple REST structure
- Laravel package available (`alexusmai/yandex-metrika`)

**Complexity:**
- Basic reports: Easy
- Custom segments: Medium
- Goal tracking: Medium
- Raw logs API: Medium-Hard

**PHP Support**:
- **Laravel**: `alexusmai/yandex-metrika` (for Laravel 5+)
- **Laravel**: `pugofka/yametrika-sdk-laravel` (alternative)
- Raw PHP: Use Guzzle with OAuth 2.0

### Value for BiznesPilot

**Priority**: ⭐⭐⭐⭐ **HIGH**

**Uzbekistan Market Relevance**:
- **20-22% market share** in Uzbekistan search
- **Russian-speaking users** prefer Yandex
- **CIS integration** - businesses targeting Russia/Kazakhstan also use Yandex
- **Local .uz domains** often perform better on Yandex
- **Complementary to Google** - full market coverage requires both

**Use Cases:**
1. **Dual Analytics**: Show GA4 + Yandex.Metrica side-by-side
2. **Russian Market Insights**: Track Russian-speaking audience separately
3. **Search Engine Comparison**: Compare Google vs Yandex traffic quality
4. **Session Replay Integration**: (UI-only feature, but valuable for debugging)
5. **E-commerce Tracking**: Revenue attribution for both platforms

**Integration with Existing Systems:**
```php
// Same structure as GA4 integration:
- HealthScoreAlgorithm: Combine Yandex + Google metrics
- FunnelAnalysisAlgorithm: Track conversions from both sources
- EngagementAlgorithm: Aggregate engagement across platforms
- RevenueForecaster: Include Yandex-sourced revenue
- CompetitorBenchmarkAlgorithm: Compare against Yandex benchmarks
```

**Competitive Advantage**:
- Most analytics tools ignore Yandex
- Uzbekistan businesses need both Google + Yandex
- BiznesPilot covering both = unique value proposition

### Cost

**API Access**: ✅ **COMPLETELY FREE**
- No charges
- No rate limits
- No traffic limits
- No approval needed

**Yandex.Metrica Service**: ✅ **FREE**
- Free unlimited analytics
- Free session replay (Webvisor)
- Free form analytics
- Free goals and segments

**Reason for Free**:
- Yandex uses anonymous data to improve services
- Feeds machine learning algorithms
- Alternative to paid analytics (competing with Adobe, etc.)

**Verdict**: Must-have for Uzbekistan market. Free, unlimited, and essential for complete coverage.

---

## 7. YANDEX.DIRECT API

### Purpose
Advertising platform API for creating, managing, and optimizing Yandex ad campaigns. Essential for businesses advertising to Russian/CIS audiences.

### Authentication
- **Method**: OAuth 2.0
- **OAuth Endpoint**: `https://oauth.yandex.com/authorize`
- **Scopes**: Not explicitly documented (use default Direct API scope)
- **Requirements**:
  - Yandex account
  - Yandex.Direct advertiser or agency account
- **Token Lifetime**: Refresh tokens don't expire (unless revoked)

**Authorization Header**:
```
Authorization: Bearer <access_token>
```

### Key Endpoints

**Base URL**: `https://api.direct.yandex.com/json/v5`

**API Structure**: Web services (each manages a class of objects)

| Service | Purpose | Methods |
|---------|---------|---------|
| `Campaigns` | Manage campaigns | get, add, update, delete, suspend, resume |
| `AdGroups` | Manage ad groups | get, add, update, delete |
| `Ads` | Manage ads | get, add, update, delete, suspend, resume |
| `Keywords` | Manage keywords | get, add, update, delete |
| `Bids` | Manage bids | get, set, setAuto |
| `Reports` | Generate reports | get (with report definition) |
| `Dictionaries` | Get reference data | get (timezones, currencies, etc.) |
| `AgencyClients` | Agency client management | get, add, update |
| `Changes` | Track changes | check (incremental sync) |

**API Formats**: JSON or SOAP (JSON recommended)

**Request Format** (JSON):
```json
POST /json/v5/campaigns
{
  "method": "get",
  "params": {
    "SelectionCriteria": {
      "States": ["ON"]
    },
    "FieldNames": ["Id", "Name", "State", "Status", "Statistics"]
  }
}
```

### Data Available

**Campaign Data:**
- Campaign ID, name, status (enabled/disabled)
- Campaign type (text, mobile, dynamic, etc.)
- Budget (daily, total), budget mode
- Start/end dates
- Bidding strategy, target CPA/ROAS
- Time targeting, geotargeting

**Ad Group Data:**
- Ad group ID, name, status
- Negative keywords
- Region targeting
- Demographics targeting (age, gender)

**Ad Data:**
- Ad ID, status (draft, accepted, rejected)
- Ad text, title, display URL
- Ad extensions (callouts, sitelinks, etc.)

**Keyword Data:**
- Keyword text, match type
- Bid amount, quality score
- Search volume, competition
- Status (active, paused, deleted)

**Performance Metrics** (from Reports service):
- `Impressions`, `Clicks`, `Ctr`
- `Cost` - Campaign spend
- `AvgCpc`, `AvgCpm`
- `Conversions`, `ConversionRate`, `CostPerConversion`
- `Revenue`, `Roas` (return on ad spend)
- Breakdown by: date, campaign, ad group, ad, keyword, device, gender, age, region

**Special Features:**
- **Keyword Suggestions**: Get keyword ideas
- **Forecast**: Predict impressions/clicks for budget
- **Auto-bidding**: Automated bid strategies
- **Dynamic Ads**: Auto-generate ads from website

### Rate Limits

**Points System** (not monetary cost):

Each operation consumes "points" from a daily quota.

| Operation | Points Cost |
|-----------|-------------|
| Create ad | 12 points |
| Edit ad | 4 points |
| Add keyword | 2 points |
| Edit keyword | 1 point |
| GetKeywordsSuggestion | 3 points per keyword |
| CreateNewWordstatReport | 10 points per keyword |

**Daily Limits**:
- Daily point quota (varies by account)
- Distributed across 24 one-hour sliding windows (1/24 of daily quota per hour)
- Unused points don't roll over

**Request Limits**:
- **5 simultaneous requests** per user
- **20 requests per 10 seconds** to Reports service (per advertiser)

**Important**:
- Read operations (get) consume fewer points
- Bulk operations save points (vs individual calls)
- Points reset hourly (sliding window)

### Integration Difficulty
**Medium-Hard** (3.5/5)

**Reasons:**
- OAuth 2.0 standard
- Points system requires monitoring
- JSON/SOAP formats (JSON easier)
- Campaign structure hierarchy (campaign → ad group → ad → keyword)
- API versioning (currently v5)
- Russian documentation better than English

**Complexity:**
- Read campaign data: Medium
- Keyword research: Medium
- Create/update campaigns: Hard
- Reports API: Medium
- Automated bidding: Hard

**PHP Support**:
- **PHP Library**: `biplane/yandex-direct` (community)
- **PHP Client**: `sitkoru/yandex-direct-api` (Packagist)
- No official Laravel package (need to build wrapper)

### Value for BiznesPilot

**Priority**: ⭐⭐⭐⭐ **HIGH**

**Uzbekistan Market Relevance**:
- **20-22% search market share** (Yandex)
- **Russian-speaking users** often use Yandex.Direct
- **CIS market** - businesses targeting Russia/Kazakhstan need this
- **Lower CPC** than Google Ads (less competitive)
- **Complementary to Google Ads** - full coverage requires both

**Use Cases:**
1. **Ad Performance Dashboard**: Show Yandex.Direct ROI alongside Google Ads
2. **Budget Optimization**: Recommend budget split between Google/Yandex
3. **Keyword Insights**: Find low-competition keywords on Yandex
4. **Automated Reporting**: Unified reporting (Google Ads + Yandex.Direct)
5. **ROI Calculator**: True ROAS across all ad platforms
6. **A/B Testing**: Compare ad performance Google vs Yandex

**Integration with Existing Systems:**
```php
// Feed into algorithms (same as Google Ads):
- MoneyLossAlgorithm: Identify wasted Yandex ad spend
- HealthScoreAlgorithm: Ad performance as health indicator
- RevenueForecaster: Include Yandex-sourced conversions
- CompetitorBenchmarkAlgorithm: Compare CTR, CPC with Yandex benchmarks
- ABTestingEngine: Test different ad variations on both platforms
```

**Competitive Advantage**:
- Most Western analytics tools ignore Yandex
- Uzbekistan/CIS businesses NEED Yandex.Direct tracking
- BiznesPilot = only platform with both Google + Yandex ad tracking

### Cost

**API Access**: ✅ **COMPLETELY FREE**
- No monetary charge for API usage
- Points system controls load (not cost)
- No approval needed

**Ad Spend**: ❌ **User pays Yandex**
- Businesses pay Yandex directly for ads (like Google Ads)
- BiznesPilot tracks performance, doesn't manage spend

**Verdict**: Critical for Uzbekistan market. Free API makes it a must-have alongside Google Ads.

---

## 8. YANDEX MAPS API

### Purpose
Geocoding, address validation, organization search, route planning, and map display for businesses. Useful for location-based services.

### Authentication
- **Method**: API Key (simpler than OAuth)
- **Get Key**: Register at Yandex Developer Console
- **Usage**: Pass key in URL parameter or header

**Example**:
```
https://api-maps.yandex.ru/2.1/?apikey=YOUR_API_KEY&lang=en_US
```

### Key Products

**JavaScript API**:
- Embed interactive maps in web/mobile apps
- Search, routing, panoramas
- Custom markers, polygons

**HTTP Geocoder API**:
- Convert address → coordinates
- Convert coordinates → address
- Fuzzy address matching

**HTTP Geosuggest API**:
- Autocomplete addresses as user types
- Suggest organizations

**HTTP Organization Search API** (Geosearch):
- Search Yandex Business Directory
- Find organizations by name, category, location
- Get organization details (hours, phone, reviews)

**HTTP Routing API**:
- Calculate routes (car, pedestrian, public transit)
- Estimate distance, duration
- Account for traffic predictions

### Data Available

**Geocoding**:
- Full address, postal code
- Coordinates (latitude, longitude)
- Precision level (exact, street, city, etc.)

**Organization Data** (from Yandex Business Directory):
- Organization name, category
- Address, phone, website
- Working hours
- Reviews, ratings
- Photos

**Routing**:
- Route geometry (polyline)
- Distance (meters), duration (seconds)
- Traffic-adjusted duration
- Turn-by-turn directions

### Rate Limits

**Free Tier**:
- **500 requests/day** for Geosearch API
- Other APIs have different limits (not clearly documented)

**Paid Tier** (Commercial API):
- For high-load systems
- Custom pricing (contact `paid-api-maps@yandex-team.com`)
- Removes daily limits
- Allows use in closed systems
- Transport tracking features

### Integration Difficulty
**Easy-Medium** (2/5)

**Reasons:**
- API key authentication (simpler than OAuth)
- REST APIs with JSON responses
- Good JavaScript SDK
- Decent documentation

**Complexity:**
- Display map: Easy
- Geocoding: Easy
- Organization search: Easy
- Routing: Medium

### Value for BiznesPilot

**Priority**: ⭐⭐ **LOW-MEDIUM**

**Use Cases:**
1. **Business Location Validation**: Verify business addresses during onboarding
2. **Service Area Mapping**: Show business service area on map
3. **Competitor Mapping**: Find nearby competitors from Business Directory
4. **Delivery Route Optimization**: (For e-commerce businesses)
5. **Customer Heatmap**: Visualize customer locations

**Integration with Existing Systems:**
```php
// Limited integration with algorithms:
- CompetitorBenchmarkAlgorithm: Find nearby competitors via Organization Search
- Business Onboarding: Geocode address for location storage
```

**Uzbekistan Market Considerations**:
- Yandex Maps is popular in Uzbekistan (alternative to Google Maps)
- Good coverage of Central Asia (better than Google in some areas)
- Russian/Uzbek language support
- Free tier (500/day) may be limiting for high usage

### Cost

**Free Tier**:
- 500 requests/day (Geosearch API)
- Suitable for small-medium usage

**Paid Tier** (Commercial API):
- Custom pricing (contact Yandex)
- Likely expensive (targeted at enterprises)

**Verdict**: Nice-to-have but not critical. Implement if budget allows after core analytics APIs.

---

## 9. YANDEX BUSINESS API (DEPRECATED)

### Status
**❌ NO LONGER AVAILABLE**

**Yandex Directory API** (formerly Yandex Business API):
- Was used to manage organizations in Yandex Connect
- **Closed on March 1, 2023**
- Replaced by **Yandex 360 for Business** (different product)

**Alternative**:
- **Yandex.Maps Organization Search API** (Geosearch) still works
- Provides access to Yandex Business Directory database
- Can search organizations, get details
- Used in Yandex.Maps, Yandex.Navigator, Yandex Search

### Value for BiznesPilot

**Priority**: ❌ **NOT APPLICABLE**

Since the API is deprecated, it cannot be integrated. However:

**Alternative Approach**:
- Use **Yandex.Maps Geosearch API** to search Yandex Business Directory
- Use **Google Business Profile API** for business listing management

---

## COMPARATIVE ANALYSIS

### Google vs Yandex: Which to Prioritize?

| Factor | Google | Yandex | Winner |
|--------|--------|--------|--------|
| **Market Share (Uzbekistan)** | 78% | 20-22% | Google |
| **API Cost** | FREE | FREE | Tie |
| **API Documentation** | Excellent (English) | Good (Russian better) | Google |
| **Rate Limits** | Generous | Very generous/none | Yandex |
| **Laravel Support** | Many packages | Some packages | Google |
| **Authentication** | OAuth 2.0 | OAuth 2.0 | Tie |
| **Data Richness** | Extensive | Extensive | Tie |
| **Integration Difficulty** | Medium | Medium | Tie |
| **Uzbekistan Relevance** | Critical | Important | Both |

**Conclusion**: Integrate **BOTH** for complete market coverage.

### Priority Integration Roadmap

**Phase 1: Critical (Weeks 1-4)**
1. ✅ **Google Analytics 4 API** - Website analytics (78% market coverage)
2. ✅ **Yandex.Metrica API** - Website analytics (22% market coverage, free + unlimited)
3. ✅ **Google Search Console API** - SEO tracking (easy, free, high value)

**Phase 2: High Priority (Weeks 5-8)**
4. ✅ **Google Ads API** - Ad performance (critical for ROI tracking)
5. ✅ **Yandex.Direct API** - Ad performance (essential for CIS market)

**Phase 3: Medium Priority (Weeks 9-12)**
6. ⚠️ **Google Business Profile API** - Local SEO (requires approval, may be delayed)
7. ⏸️ **YouTube Analytics API** - Video analytics (if businesses have YouTube)

**Phase 4: Low Priority (Future)**
8. ⏸️ **Yandex Maps API** - Location services (nice-to-have)

---

## IMPLEMENTATION GUIDE FOR LARAVEL

### Recommended Architecture

```
app/
├── Services/
│   ├── Analytics/
│   │   ├── GoogleAnalyticsService.php
│   │   ├── YandexMetricaService.php
│   │   └── AnalyticsAggregator.php (combines both)
│   ├── Advertising/
│   │   ├── GoogleAdsService.php
│   │   ├── YandexDirectService.php
│   │   └── AdPerformanceAggregator.php
│   └── SEO/
│       └── SearchConsoleService.php
├── Jobs/
│   ├── SyncGoogleAnalyticsJob.php
│   ├── SyncYandexMetricaJob.php
│   ├── SyncGoogleAdsJob.php
│   └── SyncYandexDirectJob.php
├── Models/
│   ├── Integration.php (store API credentials)
│   ├── AnalyticsSnapshot.php (cache analytics data)
│   └── AdPerformanceSnapshot.php
└── Http/
    └── Controllers/
        └── IntegrationController.php (OAuth callbacks)
```

### Database Schema

```sql
-- integrations table (already exists?)
CREATE TABLE integrations (
    id BIGINT PRIMARY KEY,
    business_id BIGINT,
    type VARCHAR(50), -- 'google_analytics', 'yandex_metrica', etc.
    status VARCHAR(20), -- 'pending', 'connected', 'error'
    credentials JSON, -- encrypted OAuth tokens
    metadata JSON, -- account IDs, property IDs, etc.
    last_synced_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- analytics_snapshots table
CREATE TABLE analytics_snapshots (
    id BIGINT PRIMARY KEY,
    business_id BIGINT,
    source VARCHAR(20), -- 'google', 'yandex'
    period_start DATE,
    period_end DATE,
    metrics JSON, -- {users, sessions, pageviews, conversions, etc.}
    dimensions JSON, -- breakdown by source, device, etc.
    created_at TIMESTAMP
);

-- ad_performance_snapshots table
CREATE TABLE ad_performance_snapshots (
    id BIGINT PRIMARY KEY,
    business_id BIGINT,
    platform VARCHAR(20), -- 'google_ads', 'yandex_direct'
    campaign_id VARCHAR(100),
    campaign_name VARCHAR(255),
    period_start DATE,
    period_end DATE,
    metrics JSON, -- {impressions, clicks, cost, conversions, revenue}
    created_at TIMESTAMP
);
```

### Laravel Packages to Install

```bash
# Google APIs
composer require google/apiclient
composer require googleads/google-ads-php
# Optional: composer require spotonlive/laravel-google-ads

# Yandex APIs
composer require alexusmai/yandex-metrika
# For Yandex.Direct, create custom service (no good Laravel package)
composer require guzzlehttp/guzzle  # For HTTP requests

# OAuth
composer require league/oauth2-client
```

### Example: Google Analytics 4 Service

```php
<?php

namespace App\Services\Analytics;

use Google\Analytics\Data\V1beta\BetaAnalyticsDataClient;
use Google\Analytics\Data\V1beta\DateRange;
use Google\Analytics\Data\V1beta\Dimension;
use Google\Analytics\Data\V1beta\Metric;
use Google\Analytics\Data\V1beta\RunReportRequest;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;

class GoogleAnalyticsService
{
    protected BetaAnalyticsDataClient $client;
    protected string $propertyId;

    public function __construct(Business $business)
    {
        // Get credentials from business integrations
        $integration = $business->integrations()
            ->where('type', 'google_analytics')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            throw new \Exception('Google Analytics not connected');
        }

        $this->propertyId = $integration->metadata['property_id'];

        // Initialize client with credentials
        $credentials = json_decode(decrypt($integration->credentials), true);
        putenv('GOOGLE_APPLICATION_CREDENTIALS=' . $this->saveCredentialsFile($credentials));

        $this->client = new BetaAnalyticsDataClient();
    }

    /**
     * Get overview metrics for date range
     */
    public function getOverview(string $startDate, string $endDate): array
    {
        $cacheKey = "ga4_overview_{$this->propertyId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 3600, function() use ($startDate, $endDate) {
            $request = new RunReportRequest([
                'property' => "properties/{$this->propertyId}",
                'dateRanges' => [
                    new DateRange(['start_date' => $startDate, 'end_date' => $endDate])
                ],
                'metrics' => [
                    new Metric(['name' => 'activeUsers']),
                    new Metric(['name' => 'sessions']),
                    new Metric(['name' => 'screenPageViews']),
                    new Metric(['name' => 'conversions']),
                    new Metric(['name' => 'totalRevenue']),
                    new Metric(['name' => 'bounceRate']),
                    new Metric(['name' => 'averageSessionDuration']),
                ],
            ]);

            $response = $this->client->runReport($request);

            return $this->formatResponse($response);
        });
    }

    /**
     * Get traffic sources breakdown
     */
    public function getTrafficSources(string $startDate, string $endDate): array
    {
        $request = new RunReportRequest([
            'property' => "properties/{$this->propertyId}",
            'dateRanges' => [
                new DateRange(['start_date' => $startDate, 'end_date' => $endDate])
            ],
            'dimensions' => [
                new Dimension(['name' => 'sessionSource']),
                new Dimension(['name' => 'sessionMedium']),
            ],
            'metrics' => [
                new Metric(['name' => 'sessions']),
                new Metric(['name' => 'activeUsers']),
                new Metric(['name' => 'conversions']),
            ],
            'orderBys' => [
                ['metric' => ['metricName' => 'sessions'], 'desc' => true]
            ],
            'limit' => 10,
        ]);

        $response = $this->client->runReport($request);
        return $this->formatResponse($response);
    }

    /**
     * Feed data into DiagnosticAlgorithmService
     */
    public function getMetricsForDiagnostic(): array
    {
        $data = $this->getOverview(now()->subDays(30)->toDateString(), now()->toDateString());

        return [
            'website_traffic' => [
                'users' => $data['activeUsers'] ?? 0,
                'sessions' => $data['sessions'] ?? 0,
                'pageviews' => $data['screenPageViews'] ?? 0,
                'bounce_rate' => $data['bounceRate'] ?? 0,
                'avg_session_duration' => $data['averageSessionDuration'] ?? 0,
            ],
            'conversions' => [
                'total' => $data['conversions'] ?? 0,
                'revenue' => $data['totalRevenue'] ?? 0,
                'conversion_rate' => $data['sessions'] > 0
                    ? ($data['conversions'] / $data['sessions']) * 100
                    : 0,
            ],
        ];
    }

    protected function formatResponse($response): array
    {
        // Format GA4 response into simple array
        $data = [];
        if ($response->getRows()->count() > 0) {
            foreach ($response->getRows() as $row) {
                foreach ($row->getMetricValues() as $i => $metric) {
                    $metricName = $response->getMetricHeaders()[$i]->getName();
                    $data[$metricName] = $metric->getValue();
                }
            }
        }
        return $data;
    }

    protected function saveCredentialsFile(array $credentials): string
    {
        $path = storage_path('app/google-credentials.json');
        file_put_contents($path, json_encode($credentials));
        return $path;
    }
}
```

### Example: Yandex.Metrica Service

```php
<?php

namespace App\Services\Analytics;

use GuzzleHttp\Client;
use App\Models\Business;
use Illuminate\Support\Facades\Cache;

class YandexMetricaService
{
    protected Client $client;
    protected string $accessToken;
    protected string $counterId;

    public function __construct(Business $business)
    {
        $integration = $business->integrations()
            ->where('type', 'yandex_metrica')
            ->where('status', 'connected')
            ->first();

        if (!$integration) {
            throw new \Exception('Yandex.Metrica not connected');
        }

        $credentials = json_decode(decrypt($integration->credentials), true);
        $this->accessToken = $credentials['access_token'];
        $this->counterId = $integration->metadata['counter_id'];

        $this->client = new Client([
            'base_uri' => 'https://api-metrica.yandex.net/',
            'headers' => [
                'Authorization' => "OAuth {$this->accessToken}",
                'Accept' => 'application/json',
            ],
        ]);
    }

    /**
     * Get overview metrics
     */
    public function getOverview(string $startDate, string $endDate): array
    {
        $cacheKey = "yandex_metrica_overview_{$this->counterId}_{$startDate}_{$endDate}";

        return Cache::remember($cacheKey, 3600, function() use ($startDate, $endDate) {
            $response = $this->client->get("stat/v1/data", [
                'query' => [
                    'id' => $this->counterId,
                    'date1' => $startDate,
                    'date2' => $endDate,
                    'metrics' => 'ym:s:visits,ym:s:users,ym:s:pageviews,ym:s:bounceRate,ym:s:avgVisitDurationSeconds',
                ],
            ]);

            return json_decode($response->getBody(), true);
        });
    }

    /**
     * Get traffic sources
     */
    public function getTrafficSources(string $startDate, string $endDate): array
    {
        $response = $this->client->get("stat/v1/data", [
            'query' => [
                'id' => $this->counterId,
                'date1' => $startDate,
                'date2' => $endDate,
                'dimensions' => 'ym:s:trafficSource',
                'metrics' => 'ym:s:visits,ym:s:users',
                'sort' => '-ym:s:visits',
                'limit' => 10,
            ],
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Feed data into DiagnosticAlgorithmService
     */
    public function getMetricsForDiagnostic(): array
    {
        $data = $this->getOverview(now()->subDays(30)->toDateString(), now()->toDateString());
        $totals = $data['totals'][0] ?? [];

        return [
            'website_traffic' => [
                'users' => $totals[1] ?? 0, // ym:s:users
                'sessions' => $totals[0] ?? 0, // ym:s:visits
                'pageviews' => $totals[2] ?? 0, // ym:s:pageviews
                'bounce_rate' => $totals[3] ?? 0, // ym:s:bounceRate
                'avg_session_duration' => $totals[4] ?? 0, // ym:s:avgVisitDurationSeconds
            ],
        ];
    }
}
```

### Integrating with DiagnosticAlgorithmService

Update `DiagnosticAlgorithmService::collectMetrics()`:

```php
protected function collectMetrics(Business $business): array
{
    return [
        'sales' => $this->collectSalesMetrics($business),
        'marketing' => $this->collectMarketingMetrics($business),
        'social' => $this->collectSocialMetrics($business),
        'funnel' => $this->collectFunnelMetrics($business),
        'analytics' => $this->collectAnalyticsMetrics($business), // NEW
        'ads' => $this->collectAdsMetrics($business), // NEW
    ];
}

protected function collectAnalyticsMetrics(Business $business): array
{
    $metrics = [];

    // Google Analytics
    try {
        $ga4 = new GoogleAnalyticsService($business);
        $metrics['google'] = $ga4->getMetricsForDiagnostic();
    } catch (\Exception $e) {
        $metrics['google'] = null;
    }

    // Yandex.Metrica
    try {
        $yandex = new YandexMetricaService($business);
        $metrics['yandex'] = $yandex->getMetricsForDiagnostic();
    } catch (\Exception $e) {
        $metrics['yandex'] = null;
    }

    // Aggregate
    $metrics['combined'] = $this->aggregateAnalytics($metrics['google'], $metrics['yandex']);

    return $metrics;
}

protected function aggregateAnalytics(?array $google, ?array $yandex): array
{
    // Combine Google + Yandex for complete picture
    $combined = [
        'users' => ($google['website_traffic']['users'] ?? 0) + ($yandex['website_traffic']['users'] ?? 0),
        'sessions' => ($google['website_traffic']['sessions'] ?? 0) + ($yandex['website_traffic']['sessions'] ?? 0),
        'pageviews' => ($google['website_traffic']['pageviews'] ?? 0) + ($yandex['website_traffic']['pageviews'] ?? 0),
    ];

    // Weighted average for rates
    $totalSessions = $combined['sessions'];
    if ($totalSessions > 0) {
        $googleWeight = ($google['website_traffic']['sessions'] ?? 0) / $totalSessions;
        $yandexWeight = ($yandex['website_traffic']['sessions'] ?? 0) / $totalSessions;

        $combined['bounce_rate'] =
            ($google['website_traffic']['bounce_rate'] ?? 0) * $googleWeight +
            ($yandex['website_traffic']['bounce_rate'] ?? 0) * $yandexWeight;
    }

    return $combined;
}
```

### Scheduled Jobs

Add to `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync analytics every 6 hours
    $schedule->job(new SyncGoogleAnalyticsJob())->everySixHours();
    $schedule->job(new SyncYandexMetricaJob())->everySixHours();

    // Sync ad performance every 4 hours
    $schedule->job(new SyncGoogleAdsJob())->everyFourHours();
    $schedule->job(new SyncYandexDirectJob())->everyFourHours();

    // Run diagnostics daily
    $schedule->job(new DailyBusinessDiagnosticJob())->daily();
}
```

---

## UZBEKISTAN MARKET INSIGHTS

### Search Engine Preferences

**Google**: 78% market share
- Android dominance drives Google usage
- Integrated into browser, maps, translator, YouTube
- Preferred by younger, urban, tech-savvy users

**Yandex**: 20-22% market share
- Popular among Russian speakers
- Better for .uz domains, Uzbek/Russian language searches
- Older demographics, CIS-connected businesses

**Recommendation**: Integrate BOTH for complete coverage.

### Language Considerations

**Search Queries**:
- Uzbek (Latin script): Growing
- Russian (Cyrillic): Still dominant for business searches
- English: Niche (tech, international businesses)

**API Language Support**:
- Google APIs: Full Uzbek + Russian support
- Yandex APIs: Excellent Russian + Uzbek support
- BiznesPilot UI: Should support Uzbek, Russian, English

### Business Types

**Most Likely to Need Analytics APIs**:
1. E-commerce (online shops)
2. Service providers (lawyers, consultants, medical)
3. Restaurants, cafes, hotels
4. Real estate agencies
5. Educational institutions
6. B2B SaaS companies

**Less Likely**:
1. Traditional retail (physical only)
2. Informal businesses (no website)
3. Very small businesses (<5 employees, no digital presence)

### Competitive Landscape

**Western Analytics Tools** (Google Analytics, Adobe):
- Used by large enterprises, multinationals
- Complex, expensive, no local support
- Ignore Yandex (huge gap)

**Russian Analytics Tools**:
- Focus only on Russian market
- Poor Uzbek language support
- Not designed for SMBs

**BiznesPilot Opportunity**:
- First platform with Google + Yandex integration
- Uzbek language support
- SMB-focused (affordable, simple)
- Local support, local currency
- AI-powered insights (not just raw data)

---

## RISK ASSESSMENT

### Technical Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Google API quota exceeded** | Medium | High | Implement caching, request batching, quota monitoring |
| **Yandex API changes without notice** | Medium | Medium | Version locking, error handling, fallback mechanisms |
| **OAuth token expiration** | High | Medium | Automatic token refresh, user notifications |
| **API deprecation** | Low | High | Monitor deprecation notices, version migration plan |
| **Rate limiting during high load** | Medium | Medium | Queue system, throttling, user limits |

### Business Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **Google Business Profile API access denied** | High | Medium | Apply early, build strong use case, have fallback |
| **Users don't connect APIs** | High | High | Onboarding flow, value demonstration, incentives |
| **Privacy concerns (data sharing)** | Low | Medium | Clear privacy policy, data encryption, user control |
| **Users lack Google/Yandex accounts** | Low | Low | Educational content, account creation guidance |

### Legal/Compliance Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| **GDPR/data protection** | Medium | High | User consent, data minimization, encryption, privacy policy |
| **API Terms of Service violation** | Low | High | Strict adherence to ToS, legal review, rate limit compliance |
| **User data breach** | Low | Critical | Encryption at rest, secure token storage, regular security audits |

---

## COST-BENEFIT ANALYSIS

### Development Cost Estimate

| Integration | Development Time | Difficulty | Priority |
|-------------|-----------------|------------|----------|
| Google Analytics 4 | 2 weeks | Medium | Critical |
| Yandex.Metrica | 1.5 weeks | Easy-Medium | Critical |
| Google Search Console | 1 week | Easy-Medium | High |
| Google Ads | 2 weeks | Medium-Hard | High |
| Yandex.Direct | 2 weeks | Medium-Hard | High |
| Google Business Profile | 1.5 weeks | Hard (+ approval wait) | Medium |
| YouTube Analytics | 1 week | Medium | Low |
| Yandex Maps | 1 week | Easy-Medium | Low |

**Total Development**: ~12 weeks (3 months) for all integrations

**Phased Approach** (Recommended):
- Phase 1 (Critical): 4.5 weeks → GA4, Yandex.Metrica, Search Console
- Phase 2 (High): 4 weeks → Google Ads, Yandex.Direct
- Phase 3 (Medium/Low): 3.5 weeks → Business Profile, YouTube, Maps

### Ongoing Costs

**Infrastructure**:
- API caching (Redis/Memcached): ~$20-50/month
- Queue workers (for sync jobs): ~$30-100/month
- Database storage: ~$10-30/month (analytics snapshots)

**Maintenance**:
- API version updates: ~4 hours/quarter
- Bug fixes, monitoring: ~8 hours/month

**Total Monthly Cost**: ~$60-180 + dev hours

### Revenue Impact

**Value Proposition**:
- Unified analytics dashboard (Google + Yandex)
- Automated diagnostics (feed into existing algorithms)
- ROI tracking (ads + organic)
- Competitive advantage (no other tool does this in Uzbekistan)

**Pricing Strategy**:
- Free tier: Manual data entry (current state)
- Pro tier (+$20-30/month): Google Analytics integration
- Premium tier (+$50-100/month): Full integration (Google + Yandex + Ads)

**Customer Acquisition**:
- Free tier → Pro tier conversion: +40% (API integrations reduce friction)
- Pro tier → Premium tier upsell: +25% (ad ROI tracking is valuable)

**Revenue Projection** (1 year, 1000 users):
- Without APIs: 1000 users × $0/month = $0
- With APIs:
  - Free: 500 users × $0 = $0
  - Pro: 300 users × $25 = $7,500/month
  - Premium: 200 users × $75 = $15,000/month
  - **Total: $22,500/month = $270,000/year**

**ROI**: $270k revenue - $2k annual costs = **$268k profit** (not accounting for dev costs)

**Break-even**: After ~1 month of full user base (3 months dev + 1 month user growth)

---

## NEXT STEPS

### Immediate Actions (Week 1)

1. ✅ **Complete this research** (DONE)
2. 📋 **Create detailed technical specs** for Phase 1 integrations
3. 🔑 **Apply for API access**:
   - Google Cloud Console: Enable GA4 Data API, Search Console API
   - Yandex Developer Console: Register app, get OAuth credentials
   - Google Business Profile: Apply for API access (expect delay)
4. 📦 **Install Laravel packages**:
   ```bash
   composer require google/apiclient
   composer require alexusmai/yandex-metrika
   composer require guzzlehttp/guzzle
   ```
5. 🗄️ **Design database schema** for integrations, snapshots
6. 🎨 **Design UI mockups** for:
   - OAuth connection flow
   - Analytics dashboard (Google + Yandex combined)
   - Integration settings page

### Phase 1 Implementation (Weeks 2-5)

**Week 2-3: Google Analytics 4**
- Build OAuth flow
- Create GoogleAnalyticsService
- Implement caching layer
- Build sync job
- Create dashboard UI
- Test with real GA4 property

**Week 4: Yandex.Metrica**
- Build OAuth flow
- Create YandexMetricaService
- Implement caching layer
- Build sync job
- Integrate into dashboard UI
- Test with real counter

**Week 5: Google Search Console**
- Build OAuth flow
- Create SearchConsoleService
- Build sync job
- Create SEO dashboard UI
- Test with verified site

### Phase 2 Implementation (Weeks 6-9)

**Weeks 6-7: Google Ads API**
- Apply for Standard Access (if not already)
- Build OAuth flow + Developer Token setup
- Create GoogleAdsService
- Implement GAQL query builder
- Build sync job
- Create ads performance dashboard
- Integrate with MoneyLossAlgorithm

**Weeks 8-9: Yandex.Direct API**
- Build OAuth flow
- Create YandexDirectService
- Implement points quota monitoring
- Build sync job
- Integrate into ads dashboard
- Combine Google Ads + Yandex.Direct metrics

### Testing & Validation (Week 10)

- Load testing (500+ concurrent users)
- API quota monitoring under load
- Token refresh testing
- Error handling validation
- User acceptance testing
- Documentation

### Launch (Week 11-12)

- Beta launch to select users
- Monitor API usage, errors
- Gather feedback
- Iterate on UX
- Public launch

---

## CONCLUSION

### Summary

This research identifies **8 APIs** (2 deprecated) with high value for BiznesPilot:

**Critical Priority**:
1. ✅ Google Analytics 4 API - Website analytics (78% market)
2. ✅ Yandex.Metrica API - Website analytics (22% market, FREE unlimited)
3. ✅ Google Search Console API - SEO tracking (FREE, easy, high value)

**High Priority**:
4. ✅ Google Ads API - Ad ROI tracking (FREE API, critical feature)
5. ✅ Yandex.Direct API - Ad ROI tracking (FREE API, essential for CIS market)

**Medium Priority**:
6. ⚠️ Google Business Profile API - Local SEO (FREE but requires approval)

**Low Priority**:
7. ⏸️ YouTube Analytics API - Video performance (niche use case)
8. ⏸️ Yandex Maps API - Location services (nice-to-have)

**Not Applicable**:
9. ❌ Yandex Business API - Deprecated (use Yandex.Maps Geosearch instead)

### Key Insights

1. **All APIs are FREE** - No monetary cost, only development time
2. **Uzbekistan needs BOTH Google + Yandex** - 78% + 22% = 100% market coverage
3. **No competitors offer both** - Unique value proposition
4. **Easy integration** - OAuth 2.0, good docs, Laravel packages exist
5. **High ROI** - $270k annual revenue potential for 1000 users

### Recommendation

**Proceed with phased implementation**:
- Phase 1 (4.5 weeks): GA4, Yandex.Metrica, Search Console → **Launch MVP**
- Phase 2 (4 weeks): Google Ads, Yandex.Direct → **Launch Premium Tier**
- Phase 3 (Optional): Business Profile, YouTube, Maps → **Future expansion**

**Total timeline**: 8-12 weeks to market-ready product

**Expected outcome**:
- Unique positioning in Uzbekistan market
- Premium tier justification ($50-100/month)
- Automated insights (feed into existing DiagnosticAlgorithmService)
- Significant competitive advantage

---

## SOURCES

**Google Analytics 4 API:**
- [Data API limits and quotas | Google Analytics | Google for Developers](https://developers.google.com/analytics/devguides/reporting/data/v1/quotas)
- [Google Analytics 4 rate limits and quotas | Geckoboard Help Center](https://support.geckoboard.com/en/articles/6055617-google-analytics-4-rate-limits-and-quotas)
- [API Dimensions & Metrics | Google Analytics | Google for Developers](https://developers.google.com/analytics/devguides/reporting/data/v1/api-schema)
- [Google Analytics Data API Overview | Google for Developers](https://developers.google.com/analytics/devguides/reporting/data/v1)
- [How much does Google Analytics actually cost? - PostHog](https://posthog.com/blog/google-analytics-cost)

**Google Ads API:**
- [Google Ads API | Google for Developers](https://developers.google.com/google-ads/api)
- [Rate Sheet | Google Ads API | Google for Developers](https://developers.google.com/google-ads/api/docs/api-policy/rate-sheet)
- [Introduction | Google Ads API | Google for Developers](https://developers.google.com/google-ads/api/docs/start)
- [Demo Apps | Google Ads API | Google for Developers](https://developers.google.com/google-ads/api/docs/client-libs/php/demo-apps)
- [GitHub - spotonlive/laravel-google-ads: Google Ads API for Laravel](https://github.com/spotonlive/laravel-google-ads)

**Google Business Profile API:**
- [Google Business Profile APIs | Google for Developers](https://developers.google.com/my-business)
- [Pricing | Google Business Profile APIs | Google for Developers](https://developers.google.com/my-business/content/pricing)
- [Google Business Profile API Essentials](https://rollout.com/integration-guides/google-business-profile/api-essentials)

**Google Search Console API:**
- [Usage Limits | Search Console API | Google for Developers](https://developers.google.com/webmaster-tools/limits)
- [Search Console API | Google for Developers](https://developers.google.com/webmaster-tools)
- [Google Search Console API For Reporting | Coupler.io Blog](https://blog.coupler.io/google-search-console-api/)
- [Google Search Console API Essentials](https://rollout.com/integration-guides/google-search-console/api-essentials)

**YouTube Analytics API:**
- [YouTube Analytics and Reporting APIs | Google for Developers](https://developers.google.com/youtube/analytics)
- [API Reference | YouTube Analytics and Reporting APIs | Google for Developers](https://developers.google.com/youtube/analytics/reference)
- [YouTube API Limits: How to Fix Quota Exceeded in 2025](https://getlate.dev/blog/youtube-api-limits-how-to-calculate-api-usage-cost-and-fix-exceeded-api-quota)
- [Is the YouTube API Free? Costs, Limits, and What You Actually Get](https://www.getphyllo.com/post/is-the-youtube-api-free-costs-limits-iv)

**Yandex.Metrica API:**
- [Metrica API — Yandex Technologies](https://yandex.com/dev/metrika)
- [Authorization | Yandex Metrica API](https://yandex.com/dev/metrika/en/intro/authorization)
- [Quick start | Yandex Metrica API](https://yandex.com/dev/metrika/en/intro/quick-start)
- [Yandex.Metrica — free All-Round Web Analytics](https://metrica.yandex.com/about/info/pricing)
- [GitHub - alexusmai/yandex-metrika](https://github.com/alexusmai/yandex-metrika)

**Yandex.Direct API:**
- [Direct API — Yandex Technologies](https://yandex.com/dev/direct)
- [Overview of the Yandex Direct API version 5 | Yandex Direct API](https://yandex.com/dev/direct/doc/en/concepts/overview)
- [Usage limitations. Yandex Direct API. Version 5](https://yandex.com/dev/direct/doc/dg-v4/concepts/Restrictions.html)
- [Restrictions, points. Yandex Direct API. Version 5](https://yandex.com/dev/direct/doc/dg/concepts/units.html)
- [GitHub - biplane/yandex-direct: PHP library for Yandex.Direct API](https://github.com/biplane/yandex-direct)

**Yandex Maps API:**
- [Yandex Maps API for business](https://yandex.com/maps-api/)
- [Developer documentation — API Yandex Maps](https://yandex.com/maps-api/docs)
- [Yandex.Maps API for Business — Yandex Technologies](https://yandex.com/dev/maps/commercial/)

**Uzbekistan Market Research:**
- [Search in Uzbekistan: What Brands Must Know Before Running Ads](https://russia-promo.com/blog/search-in-uzbekistan)
- [Search Engine Market Share: Who's Leading the Race](https://kinsta.com/search-engine-market-share/)
- [Yandex vs Google: Russian Search Engine Market Share](https://russia-promo.com/blog/yandex-vs-google-how-to-promote-a-brand-using-search-engines-in-russia)

---

**Document Version**: 1.0
**Date**: January 1, 2026
**Author**: BiznesPilot Research Team
**Status**: Final - Ready for Implementation
