export type ShowcaseProperty = {
  name: string;
  type: string;
  location: string;
  priceRange: string;
  occupancy: string;
  yield: string;
  highlight: string;
  description: string;
  amenities: string[];
};

export type MarketCluster = {
  name: string;
  profile: string;
  avgRent: string;
  vacancy: string;
  demand: string;
};

export const propertyStats = [
  { value: '36', label: 'sample properties in active pipeline' },
  { value: '94%', label: 'average occupancy across featured stock' },
  { value: '18 days', label: 'average turnaround on prepared units' },
  { value: '4.8/5', label: 'resident experience score in sample reviews' },
];

export const showcaseProperties: ShowcaseProperty[] = [
  {
    name: 'Riverside Crest Apartments',
    type: 'Urban residential',
    location: 'Westlands, Nairobi',
    priceRange: 'KES 65K - 110K / month',
    occupancy: '97% occupied',
    yield: '8.9% net yield',
    highlight: 'Rooftop lounge + concierge-ready front desk',
    description:
      'A premium mid-rise rental community designed for young professionals who need fast access to the CBD, secure parking, and responsive support workflows.',
    amenities: ['Smart access', 'Backup power', 'Fitness studio', 'Resident lounge'],
  },
  {
    name: 'Kilimani Courtyard Homes',
    type: 'Family apartments',
    location: 'Kilimani, Nairobi',
    priceRange: 'KES 85K - 145K / month',
    occupancy: '92% occupied',
    yield: '7.8% net yield',
    highlight: 'Quiet compound with strong family retention',
    description:
      'A larger-format property mix built around long-stay tenants, better parking circulation, and predictable maintenance planning for family-sized units.',
    amenities: ['Children play zone', 'Water storage', 'CCTV coverage', 'Garden court'],
  },
  {
    name: 'Greenview Lofts',
    type: 'Mixed-use rental',
    location: 'Syokimau, Nairobi Metro',
    priceRange: 'KES 48K - 95K / month',
    occupancy: '95% occupied',
    yield: '9.4% net yield',
    highlight: 'Ground-floor retail with commuter-oriented units',
    description:
      'This mixed-use block combines compact apartments and service retail, making it ideal for a digitally managed portfolio that needs cleaner tenant visibility and turnover control.',
    amenities: ['Retail frontage', 'High-speed internet', 'Access control', 'Delivery lockers'],
  },
];

export const propertyExperiencePillars = [
  {
    title: 'Resident-ready operations',
    text: 'Digital inquiry capture, faster issue logging, and clear support handoff keep the tenant journey consistent from move-in to renewal.',
  },
  {
    title: 'Portfolio visibility',
    text: 'Track occupancy, rent status, service issues, and readiness signals in one operating view instead of splitting work across chats and spreadsheets.',
  },
  {
    title: 'Marketing + retention',
    text: 'Modern presentation pages, amenity storytelling, and localized insights help properties attract the right tenants while improving renewal confidence.',
  },
];

export const marketClusters: MarketCluster[] = [
  {
    name: 'Westlands Core',
    profile: 'High-demand professional housing with strong amenity expectations and faster lead conversion when response times are tight.',
    avgRent: 'KES 88K',
    vacancy: '3.8%',
    demand: 'Strong premium demand',
  },
  {
    name: 'Kilimani Residential',
    profile: 'Balanced family and executive tenant mix where parking quality, security, and service consistency drive longer occupancy cycles.',
    avgRent: 'KES 112K',
    vacancy: '5.1%',
    demand: 'Stable long-stay demand',
  },
  {
    name: 'Syokimau Growth Belt',
    profile: 'Commuter-driven expansion zone suited to mixed-use and value-conscious residential products with room for operational differentiation.',
    avgRent: 'KES 61K',
    vacancy: '6.4%',
    demand: 'Growing commuter demand',
  },
];

export const propertyFaqs = [
  {
    question: 'What does this sample property page represent?',
    answer:
      'It is a marketing-ready showcase for how Starmax can present residential and mixed-use properties with portfolio metrics, leasing highlights, and supporting content.',
  },
  {
    question: 'Can these listings connect to live property data later?',
    answer:
      'Yes. The current content is seeded sample data, but the same structure can be wired to an API, CMS, or admin-managed inventory feed when needed.',
  },
  {
    question: 'Why include blog articles on a property page?',
    answer:
      'Insight content improves trust, adds search relevance, and gives landlords or prospects more context about operations, maintenance, and neighborhood positioning.',
  },
];