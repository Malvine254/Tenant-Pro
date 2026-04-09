import demoTenantOps from '../data/demo-tenant-ops.json';

export type DemoRoleName = 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';

export type DemoUser = {
  id: string;
  phoneNumber: string;
  email?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  role: DemoRoleName;
  isActive: boolean;
  createdAt?: string;
};

export type DemoProperty = {
  id: string;
  name: string;
  description?: string | null;
  coverImageUrl?: string | null;
  addressLine: string;
  city: string;
  state?: string | null;
  country: string;
  landlordId: string;
  landlord?: {
    firstName?: string | null;
    lastName?: string | null;
    phoneNumber?: string;
  };
  units: Array<{
    id: string;
    unitNumber: string;
    floor?: string | null;
    rentAmount: number | string;
    status: string;
    imageUrls?: string[] | null;
  }>;
};

export type DemoInvoice = {
  id: string;
  billingType: string;
  periodMonth: number;
  periodYear: number;
  dueDate: string;
  totalAmount: number | string;
  paidAmount: number | string;
  status: string;
  tenant?: {
    user?: {
      firstName?: string | null;
      lastName?: string | null;
      phoneNumber?: string;
    };
  };
  unit?: {
    unitNumber?: string;
    property?: {
      name?: string;
    };
  };
};

export type DemoMaintenanceRequest = {
  id: string;
  title: string;
  description: string;
  priority: 'LOW' | 'MEDIUM' | 'HIGH' | 'URGENT';
  status: 'OPEN' | 'IN_PROGRESS' | 'RESOLVED' | 'CLOSED';
  createdAt: string;
  unit?: {
    unitNumber?: string;
    property?: { name?: string };
  };
  tenant?: {
    user?: { firstName?: string | null; lastName?: string | null; phoneNumber?: string };
  };
  assignedTo?: {
    firstName?: string | null;
    lastName?: string | null;
    phoneNumber?: string;
  } | null;
};

export type DemoConversationSummary = {
  id: string;
  tenantUserId: string;
  tenantName: string;
  phoneNumber: string;
  email?: string | null;
  isTenantActive: boolean;
  topic: string;
  subject: string;
  isOpen: boolean;
  lastMessage: string;
  lastMessageAt: string;
  lastSender: 'tenant' | 'staff' | null;
};

export type DemoSupportMessage = {
  id: string;
  senderId: string;
  topic: string;
  message: string;
  attachmentUri?: string | null;
  attachmentName?: string | null;
  isFromTenant: boolean;
  status: string;
  timestamp: number;
};

export type DemoTenantOpsDataset = {
  users: DemoUser[];
  properties: DemoProperty[];
  invoices: DemoInvoice[];
  maintenance: DemoMaintenanceRequest[];
  conversations: DemoConversationSummary[];
  messagesByConversation: Record<string, DemoSupportMessage[]>;
};

const DEMO_DATA_KEY = 'tenant_pro_demo_data';

function cloneDefaultDataset(): DemoTenantOpsDataset {
  return JSON.parse(JSON.stringify(demoTenantOps)) as DemoTenantOpsDataset;
}

export function getDemoDataset(): DemoTenantOpsDataset {
  if (typeof window === 'undefined') {
    return cloneDefaultDataset();
  }

  const raw = sessionStorage.getItem(DEMO_DATA_KEY);
  if (!raw) {
    const seeded = cloneDefaultDataset();
    sessionStorage.setItem(DEMO_DATA_KEY, JSON.stringify(seeded));
    return seeded;
  }

  try {
    return JSON.parse(raw) as DemoTenantOpsDataset;
  } catch {
    const seeded = cloneDefaultDataset();
    sessionStorage.setItem(DEMO_DATA_KEY, JSON.stringify(seeded));
    return seeded;
  }
}

export function saveDemoDataset(data: DemoTenantOpsDataset) {
  if (typeof window === 'undefined') return;
  sessionStorage.setItem(DEMO_DATA_KEY, JSON.stringify(data));
}

export function resetDemoDataset() {
  if (typeof window === 'undefined') return;
  sessionStorage.removeItem(DEMO_DATA_KEY);
}
