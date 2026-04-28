"use client";

import { useSearchParams } from 'next/navigation';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { apiRequest } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset, saveDemoDataset } from '../../../lib/demo-tenant-ops';

import { API_BASE_URL } from '../../../lib/api';

const FALLBACK_UNIT_IMG = 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80';
const FALLBACK_COVER = 'https://images.unsplash.com/photo-1545324418-cc1a3fa10c00?auto=format&fit=crop&w=1600&q=80';

function mediaUrl(path: string | null | undefined): string | null {
  if (!path) return null;
  if (path.startsWith('http')) return path;
  return `${API_BASE_URL.replace('/api', '')}${path}`;
}

const UNIT_IMGS = [
  'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80',
  'https://images.unsplash.com/photo-1484154218962-a197022b5858?auto=format&fit=crop&w=800&q=80',
];

type OccupantUser = {
  id: string;
  firstName?: string | null;
  lastName?: string | null;
  phoneNumber: string;
  profileImageUrl?: string | null;
};

type UnitRow = {
  id: string;
  unitNumber: string;
  floor?: string | null;
  rentAmount: number | string;
  status: string;
  imageUrls?: string[] | null;
  tenants?: Array<{ id: string; isActive?: boolean; user: OccupantUser }>;
};

type PropertyRow = {
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
  units: UnitRow[];
};

type LandlordRow = {
  id: string;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  firstName?: string | null;
  lastName?: string | null;
  phoneNumber: string;
};

function statusColor(status: string) {
  if (status === 'VACANT') return 'bg-emerald-500';
  if (status === 'MAINTENANCE') return 'bg-amber-500';
  return 'bg-slate-500';
}

function statusBadge(status: string) {
  if (status === 'VACANT') return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200';
  if (status === 'MAINTENANCE') return 'bg-amber-50 text-amber-700 ring-1 ring-amber-200';
  return 'bg-slate-100 text-slate-700 ring-1 ring-slate-200';
}

function unitImg(unit: UnitRow, property: PropertyRow, index: number): string {
  const raw = unit.imageUrls?.[0] || property.coverImageUrl;
  return mediaUrl(raw) || UNIT_IMGS[index % UNIT_IMGS.length] || FALLBACK_UNIT_IMG;
}

function occupantName(user: OccupantUser): string {
  return [user.firstName, user.lastName].filter(Boolean).join(' ') || user.phoneNumber;
}

function groupByFloor(units: UnitRow[]): Map<string, UnitRow[]> {
  const map = new Map<string, UnitRow[]>();
  for (const unit of units) {
    const key = unit.floor ? `Floor ${unit.floor}` : 'Ground Floor';
    const existing = map.get(key) ?? [];
    existing.push(unit);
    map.set(key, existing);
  }
  return map;
}

export default function PropertiesPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';

  const [rows, setRows] = useState<PropertyRow[]>([]);
  const [landlords, setLandlords] = useState<LandlordRow[]>([]);
  const [tenants, setTenants] = useState<LandlordRow[]>([]);
  const [selectedPropertyId, setSelectedPropertyId] = useState<string>('');
  const [expandedPropertyId, setExpandedPropertyId] = useState<string | null>(null);
  const [activeFloor, setActiveFloor] = useState<Record<string, string>>({});
  const [editingPropertyId, setEditingPropertyId] = useState<string | null>(null);
  const [editingUnitId, setEditingUnitId] = useState<string | null>(null);
  const [showAddPanel, setShowAddPanel] = useState(false);
  const [activeTab, setActiveTab] = useState<'createProperty' | 'addUnit'>('createProperty');
  const [error, setError] = useState<string | null>(null);

  const [propertyForm, setPropertyForm] = useState({
    landlordId: '',
    name: '',
    description: '',
    coverImageUrl: '',
    addressLine: '',
    city: '',
    state: '',
    country: 'Kenya',
  });

  const [propertyEditForm, setPropertyEditForm] = useState({
    landlordId: '',
    name: '',
    description: '',
    coverImageUrl: '',
    addressLine: '',
    city: '',
    state: '',
    country: 'Kenya',
  });

  const [unitForm, setUnitForm] = useState({
    unitNumber: '',
    floor: '',
    rentAmount: '',
    status: 'VACANT',
    imageUrls: '',
  });

  const [unitEditForm, setUnitEditForm] = useState({
    unitNumber: '',
    floor: '',
    rentAmount: '',
    status: 'VACANT',
    imageUrls: '',
  });

  const [assignmentForm, setAssignmentForm] = useState({
    tenantId: '',
    propertyId: '',
    unitId: '',
    phoneNumber: '',
    expiresInHours: '72',
  });
  const [assignmentMessage, setAssignmentMessage] = useState<string | null>(null);

  const parseImageUrls = (value: string) =>
    value.split('\n').map((s) => s.trim()).filter(Boolean);

  const hydrateWorkspace = (properties: PropertyRow[], users: LandlordRow[]) => {
    setRows(properties);
    const landlordRows = users.filter((u) => u.role === 'LANDLORD');
    const tenantRows = users.filter((u) => u.role === 'TENANT');
    setLandlords(landlordRows);
    setTenants(tenantRows);

    if (!selectedPropertyId && properties[0]) setSelectedPropertyId(properties[0].id);
    if (!propertyForm.landlordId && landlordRows[0])
      setPropertyForm((p) => ({ ...p, landlordId: landlordRows[0].id }));
    if (!assignmentForm.tenantId && tenantRows[0])
      setAssignmentForm((p) => ({ ...p, tenantId: tenantRows[0].id, phoneNumber: tenantRows[0].phoneNumber }));
    if (!assignmentForm.propertyId && properties[0]) {
      const firstUnit = properties[0].units.find((u) => u.status !== 'OCCUPIED') ?? properties[0].units[0];
      setAssignmentForm((p) => ({ ...p, propertyId: properties[0].id, unitId: firstUnit?.id ?? p.unitId }));
    }

    if (!expandedPropertyId && properties[0]) setExpandedPropertyId(properties[0].id);
  };

  const load = async () => {
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }
      const session = getSession();
      if (!session) return;
      const [properties, users] = await Promise.all([
        apiRequest<PropertyRow[]>('/properties', session.accessToken),
        apiRequest<LandlordRow[]>('/users', session.accessToken),
      ]);
      hydrateWorkspace(properties, users);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to load properties');
    }
  };

  useEffect(() => { void load(); }, [isDemoMode]);

  const submitProperty = async (event: FormEvent) => {
    event.preventDefault();
    try {
      setError(null);
      if (isDemoMode) {
        const dataset = getDemoDataset();
        const landlord = landlords.find((l) => l.id === propertyForm.landlordId);
        const newProperty: PropertyRow = {
          id: `demo-property-${Date.now()}`,
          name: propertyForm.name,
          description: propertyForm.description || null,
          coverImageUrl: propertyForm.coverImageUrl || null,
          addressLine: propertyForm.addressLine,
          city: propertyForm.city,
          state: propertyForm.state || null,
          country: propertyForm.country,
          landlordId: propertyForm.landlordId,
          landlord: { firstName: landlord?.firstName, lastName: landlord?.lastName, phoneNumber: landlord?.phoneNumber },
          units: [],
        };
        dataset.properties.unshift(newProperty);
        saveDemoDataset(dataset);
        setPropertyForm((p) => ({ ...p, name: '', description: '', coverImageUrl: '', addressLine: '', city: '', state: '' }));
        setSelectedPropertyId(newProperty.id);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        setShowAddPanel(false);
        return;
      }
      const session = getSession();
      if (!session) return;
      await apiRequest('/properties', session.accessToken, { method: 'POST', body: JSON.stringify(propertyForm) });
      setPropertyForm((p) => ({ ...p, name: '', description: '', coverImageUrl: '', addressLine: '', city: '', state: '' }));
      await load();
      setShowAddPanel(false);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to create property');
    }
  };

  const submitUnit = async (event: FormEvent) => {
    event.preventDefault();
    if (!selectedPropertyId) return;
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.properties = dataset.properties.map((p) =>
          p.id === selectedPropertyId
            ? { ...p, units: [...p.units, { id: `demo-unit-${Date.now()}`, unitNumber: unitForm.unitNumber, floor: unitForm.floor || null, rentAmount: Number(unitForm.rentAmount), status: unitForm.status, imageUrls: parseImageUrls(unitForm.imageUrls), tenants: [] }] }
            : p,
        );
        saveDemoDataset(dataset);
        setUnitForm({ unitNumber: '', floor: '', rentAmount: '', status: 'VACANT', imageUrls: '' });
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        setShowAddPanel(false);
        return;
      }
      const session = getSession();
      if (!session) return;
      await apiRequest(`/properties/${selectedPropertyId}/units`, session.accessToken, {
        method: 'POST',
        body: JSON.stringify({ unitNumber: unitForm.unitNumber, floor: unitForm.floor || undefined, rentAmount: Number(unitForm.rentAmount), status: unitForm.status, imageUrls: parseImageUrls(unitForm.imageUrls) }),
      });
      setUnitForm({ unitNumber: '', floor: '', rentAmount: '', status: 'VACANT', imageUrls: '' });
      await load();
      setShowAddPanel(false);
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to add unit');
    }
  };

  const startEditProperty = (property: PropertyRow) => {
    setEditingPropertyId(property.id);
    setPropertyEditForm({ landlordId: property.landlordId, name: property.name, description: property.description ?? '', coverImageUrl: property.coverImageUrl ?? '', addressLine: property.addressLine, city: property.city, state: property.state ?? '', country: property.country });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const submitEditProperty = async (event: FormEvent) => {
    event.preventDefault();
    if (!editingPropertyId) return;
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        const landlord = landlords.find((l) => l.id === propertyEditForm.landlordId);
        dataset.properties = dataset.properties.map((p) =>
          p.id === editingPropertyId
            ? { ...p, ...propertyEditForm, description: propertyEditForm.description || null, coverImageUrl: propertyEditForm.coverImageUrl || null, state: propertyEditForm.state || null, landlord: { firstName: landlord?.firstName, lastName: landlord?.lastName, phoneNumber: landlord?.phoneNumber } }
            : p,
        );
        saveDemoDataset(dataset);
        setEditingPropertyId(null);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }
      const session = getSession();
      if (!session) return;
      await apiRequest(`/properties/${editingPropertyId}`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({ ...propertyEditForm, coverImageUrl: propertyEditForm.coverImageUrl || undefined, description: propertyEditForm.description || undefined, state: propertyEditForm.state || undefined }),
      });
      setEditingPropertyId(null);
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to update property');
    }
  };

  const startEditUnit = (unit: UnitRow) => {
    setEditingUnitId(unit.id);
    setUnitEditForm({ unitNumber: unit.unitNumber, floor: unit.floor ?? '', rentAmount: String(unit.rentAmount), status: unit.status, imageUrls: (unit.imageUrls ?? []).join('\n') });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  };

  const submitEditUnit = async (event: FormEvent) => {
    event.preventDefault();
    if (!editingUnitId) return;
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.properties = dataset.properties.map((p) => ({ ...p, units: p.units.map((u) => u.id === editingUnitId ? { ...u, unitNumber: unitEditForm.unitNumber, floor: unitEditForm.floor || null, rentAmount: Number(unitEditForm.rentAmount), status: unitEditForm.status, imageUrls: parseImageUrls(unitEditForm.imageUrls) } : u) }));
        saveDemoDataset(dataset);
        setEditingUnitId(null);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }
      const session = getSession();
      if (!session) return;
      await apiRequest(`/properties/units/${editingUnitId}`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({ unitNumber: unitEditForm.unitNumber, floor: unitEditForm.floor || undefined, rentAmount: Number(unitEditForm.rentAmount), status: unitEditForm.status, imageUrls: parseImageUrls(unitEditForm.imageUrls) }),
      });
      setEditingUnitId(null);
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to update unit');
    }
  };

  const assignmentUnits = useMemo(() => {
    const property = rows.find((p) => p.id === assignmentForm.propertyId);
    return property?.units.filter((u) => u.status !== 'OCCUPIED') ?? [];
  }, [assignmentForm.propertyId, rows]);

  const submitAssignment = async (event: FormEvent) => {
    event.preventDefault();
    try {
      setAssignmentMessage(null);
      if (isDemoMode) {
        const dataset = getDemoDataset();
        const code = `DMO-${Math.floor(100000 + Math.random() * 900000)}`;
        dataset.properties = dataset.properties.map((p) =>
          p.id === assignmentForm.propertyId
            ? { ...p, units: p.units.map((u) => u.id === assignmentForm.unitId ? { ...u, status: 'OCCUPIED' } : u) }
            : p,
        );
        saveDemoDataset(dataset);
        setAssignmentMessage(`Code ${code} created. Share it with the tenant to link their apartment.`);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }
      const session = getSession();
      if (!session) return;
      const invitation = await apiRequest<{ code: string; expiresAt: string }>('/invitations', session.accessToken, {
        method: 'POST',
        body: JSON.stringify({ propertyId: assignmentForm.propertyId, unitId: assignmentForm.unitId, phoneNumber: assignmentForm.phoneNumber, expiresInHours: Number(assignmentForm.expiresInHours || 72), sentVia: 'ADMIN_DASHBOARD' }),
      });
      setAssignmentMessage(`Code ${invitation.code} created. Tenant opens app → Account → Link Apartment and enters this code.`);
      await load();
    } catch (e) {
      setError(e instanceof Error ? e.message : 'Failed to assign room');
    }
  };

  const totalUnits = rows.reduce((acc, p) => acc + p.units.length, 0);
  const occupiedUnits = rows.reduce((acc, p) => acc + p.units.filter((u) => u.status === 'OCCUPIED').length, 0);
  const vacantUnits = totalUnits - occupiedUnits;

  return (
    <main className="mx-auto w-full max-w-7xl space-y-6 pb-12 text-gray-900">

      {/* ── Header ── */}
      <section className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-indigo-950 to-cyan-900 px-6 py-10 text-white shadow-xl">
        <div className="relative z-10">
          <p className="text-xs font-semibold uppercase tracking-widest text-indigo-300">Property Manager</p>
          <h1 className="mt-1 text-3xl font-bold tracking-tight">My Properties</h1>
          <p className="mt-1 text-sm text-slate-300">Floor-by-floor view of every unit, its status, and current occupant.</p>
          <div className="mt-4 flex flex-wrap gap-3 text-xs">
            <span className="rounded-full bg-white/10 px-3 py-1.5 backdrop-blur-sm">{rows.length} buildings</span>
            <span className="rounded-full bg-white/10 px-3 py-1.5 backdrop-blur-sm">{totalUnits} units</span>
            <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-200">{vacantUnits} vacant</span>
            <span className="rounded-full bg-rose-400/20 px-3 py-1.5 text-rose-200">{occupiedUnits} occupied</span>
          </div>
          <button
            type="button"
            onClick={() => { setShowAddPanel(!showAddPanel); setEditingPropertyId(null); setEditingUnitId(null); }}
            className="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2 text-sm font-semibold text-slate-900 shadow hover:bg-slate-50 transition"
          >
            <span className="text-lg leading-none">＋</span> Add Property / Unit
          </button>
        </div>
        <div className="pointer-events-none absolute -right-16 -top-16 h-64 w-64 rounded-full bg-indigo-400/10 blur-3xl" />
        <div className="pointer-events-none absolute -bottom-10 left-1/3 h-40 w-40 rounded-full bg-cyan-400/10 blur-3xl" />
      </section>

      {error ? (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
      ) : null}

      {/* ── Add / Edit Panel ── */}
      {(showAddPanel || editingPropertyId || editingUnitId) ? (
        <section className="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
          {editingPropertyId ? (
            <form onSubmit={submitEditProperty} className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold">Edit Property</h3>
                <button type="button" onClick={() => setEditingPropertyId(null)} className="text-xs text-gray-500 hover:text-gray-700">✕ Cancel</button>
              </div>
              <select value={propertyEditForm.landlordId} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, landlordId: e.target.value })} className="tp-select" required>
                {landlords.map((l) => <option key={l.id} value={l.id}>{[l.firstName, l.lastName].filter(Boolean).join(' ') || l.phoneNumber}</option>)}
              </select>
              <div className="grid gap-3 md:grid-cols-2">
                <input value={propertyEditForm.name} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, name: e.target.value })} className="tp-input" placeholder="Property name" required />
                <input value={propertyEditForm.coverImageUrl} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, coverImageUrl: e.target.value })} className="tp-input" placeholder="Cover image URL" />
              </div>
              <textarea value={propertyEditForm.description} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, description: e.target.value })} className="tp-textarea" rows={2} placeholder="Description" />
              <input value={propertyEditForm.addressLine} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, addressLine: e.target.value })} className="tp-input" placeholder="Address" required />
              <div className="grid gap-3 md:grid-cols-3">
                <input value={propertyEditForm.city} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, city: e.target.value })} className="tp-input" placeholder="City" required />
                <input value={propertyEditForm.state} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, state: e.target.value })} className="tp-input" placeholder="State" />
                <input value={propertyEditForm.country} onChange={(e) => setPropertyEditForm({ ...propertyEditForm, country: e.target.value })} className="tp-input" placeholder="Country" required />
              </div>
              <button type="submit" className="tp-primary-btn">Save Changes</button>
            </form>
          ) : editingUnitId ? (
            <form onSubmit={submitEditUnit} className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-base font-semibold">Edit Unit</h3>
                <button type="button" onClick={() => setEditingUnitId(null)} className="text-xs text-gray-500 hover:text-gray-700">✕ Cancel</button>
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                <input value={unitEditForm.unitNumber} onChange={(e) => setUnitEditForm({ ...unitEditForm, unitNumber: e.target.value })} className="tp-input" placeholder="Unit number" required />
                <input value={unitEditForm.floor} onChange={(e) => setUnitEditForm({ ...unitEditForm, floor: e.target.value })} className="tp-input" placeholder="Floor (e.g. 1, 2, G)" />
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                <input type="number" step="0.01" value={unitEditForm.rentAmount} onChange={(e) => setUnitEditForm({ ...unitEditForm, rentAmount: e.target.value })} className="tp-input" placeholder="Rent amount (KES)" required />
                <select value={unitEditForm.status} onChange={(e) => setUnitEditForm({ ...unitEditForm, status: e.target.value })} className="tp-select">
                  <option value="VACANT">VACANT</option>
                  <option value="OCCUPIED">OCCUPIED</option>
                  <option value="MAINTENANCE">MAINTENANCE</option>
                </select>
              </div>
              <textarea value={unitEditForm.imageUrls} onChange={(e) => setUnitEditForm({ ...unitEditForm, imageUrls: e.target.value })} className="tp-textarea" rows={3} placeholder="Room photo URLs, one per line" />
              <button type="submit" className="tp-primary-btn">Save Unit</button>
            </form>
          ) : (
            <>
              <div className="mb-4 flex items-center justify-between">
                <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
                  {(['createProperty', 'addUnit'] as const).map((tab) => (
                    <button key={tab} type="button" onClick={() => setActiveTab(tab)}
                      className={`rounded-lg px-4 py-2 text-sm font-medium transition ${activeTab === tab ? 'bg-white text-slate-900 shadow-sm' : 'text-gray-500 hover:text-gray-900'}`}>
                      {tab === 'createProperty' ? 'New Property' : 'Add Unit'}
                    </button>
                  ))}
                </div>
                <button type="button" onClick={() => setShowAddPanel(false)} className="text-xs text-gray-500 hover:text-gray-700">✕ Close</button>
              </div>

              {activeTab === 'createProperty' ? (
                <form onSubmit={submitProperty} className="space-y-3">
                  <select value={propertyForm.landlordId} onChange={(e) => setPropertyForm({ ...propertyForm, landlordId: e.target.value })} className="tp-select" required>
                    <option value="">Select landlord</option>
                    {landlords.map((l) => <option key={l.id} value={l.id}>{[l.firstName, l.lastName].filter(Boolean).join(' ') || l.phoneNumber}</option>)}
                  </select>
                  <div className="grid gap-3 md:grid-cols-2">
                    <input value={propertyForm.name} onChange={(e) => setPropertyForm({ ...propertyForm, name: e.target.value })} className="tp-input" placeholder="Property name" required />
                    <input value={propertyForm.coverImageUrl} onChange={(e) => setPropertyForm({ ...propertyForm, coverImageUrl: e.target.value })} className="tp-input" placeholder="Cover image URL" />
                  </div>
                  <textarea value={propertyForm.description} onChange={(e) => setPropertyForm({ ...propertyForm, description: e.target.value })} className="tp-textarea" rows={2} placeholder="Description (optional)" />
                  <input value={propertyForm.addressLine} onChange={(e) => setPropertyForm({ ...propertyForm, addressLine: e.target.value })} className="tp-input" placeholder="Address" required />
                  <div className="grid gap-3 md:grid-cols-3">
                    <input value={propertyForm.city} onChange={(e) => setPropertyForm({ ...propertyForm, city: e.target.value })} className="tp-input" placeholder="City" required />
                    <input value={propertyForm.state} onChange={(e) => setPropertyForm({ ...propertyForm, state: e.target.value })} className="tp-input" placeholder="State" />
                    <input value={propertyForm.country} onChange={(e) => setPropertyForm({ ...propertyForm, country: e.target.value })} className="tp-input" placeholder="Country" required />
                  </div>
                  <button type="submit" className="tp-primary-btn">Create Property</button>
                </form>
              ) : (
                <form onSubmit={submitUnit} className="space-y-3">
                  <select value={selectedPropertyId} onChange={(e) => setSelectedPropertyId(e.target.value)} className="tp-select" required>
                    <option value="">Select property</option>
                    {rows.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
                  </select>
                  <div className="grid gap-3 md:grid-cols-2">
                    <input value={unitForm.unitNumber} onChange={(e) => setUnitForm({ ...unitForm, unitNumber: e.target.value })} className="tp-input" placeholder="Unit number (e.g. A1, 101)" required />
                    <input value={unitForm.floor} onChange={(e) => setUnitForm({ ...unitForm, floor: e.target.value })} className="tp-input" placeholder="Floor (e.g. 1, 2, G)" />
                  </div>
                  <div className="grid gap-3 md:grid-cols-2">
                    <input type="number" step="0.01" value={unitForm.rentAmount} onChange={(e) => setUnitForm({ ...unitForm, rentAmount: e.target.value })} className="tp-input" placeholder="Rent (KES)" required />
                    <select value={unitForm.status} onChange={(e) => setUnitForm({ ...unitForm, status: e.target.value })} className="tp-select">
                      <option value="VACANT">VACANT</option>
                      <option value="OCCUPIED">OCCUPIED</option>
                      <option value="MAINTENANCE">MAINTENANCE</option>
                    </select>
                  </div>
                  <textarea value={unitForm.imageUrls} onChange={(e) => setUnitForm({ ...unitForm, imageUrls: e.target.value })} className="tp-textarea" rows={3} placeholder="Room photo URLs, one per line" />
                  <button type="submit" className="tp-primary-btn">Add Unit</button>
                </form>
              )}
            </>
          )}
        </section>
      ) : null}

      {/* ── Assign Room Panel ── */}
      <section className="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
        <div className="mb-3">
          <h3 className="text-sm font-semibold text-gray-900">Send Invitation Code</h3>
          <p className="text-xs text-gray-500 mt-0.5">Generate a code so a tenant can link their unit from the mobile app.</p>
        </div>
        <form onSubmit={submitAssignment} className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
          <select value={assignmentForm.tenantId} onChange={(e) => { const t = tenants.find((x) => x.id === e.target.value); setAssignmentForm((p) => ({ ...p, tenantId: e.target.value, phoneNumber: t?.phoneNumber ?? p.phoneNumber })); }} className="tp-select" required>
            <option value="">Tenant</option>
            {tenants.map((t) => <option key={t.id} value={t.id}>{[t.firstName, t.lastName].filter(Boolean).join(' ') || t.phoneNumber}</option>)}
          </select>
          <select value={assignmentForm.propertyId} onChange={(e) => { const p = rows.find((x) => x.id === e.target.value); const u = p?.units.find((x) => x.status !== 'OCCUPIED') ?? p?.units[0]; setAssignmentForm((prev) => ({ ...prev, propertyId: e.target.value, unitId: u?.id ?? '' })); }} className="tp-select" required>
            <option value="">Property</option>
            {rows.map((p) => <option key={p.id} value={p.id}>{p.name}</option>)}
          </select>
          <select value={assignmentForm.unitId} onChange={(e) => setAssignmentForm((p) => ({ ...p, unitId: e.target.value }))} className="tp-select" required>
            <option value="">Unit</option>
            {assignmentUnits.map((u) => <option key={u.id} value={u.id}>{u.unitNumber} · KES {Number(u.rentAmount).toLocaleString()}</option>)}
          </select>
          <input value={assignmentForm.phoneNumber} onChange={(e) => setAssignmentForm((p) => ({ ...p, phoneNumber: e.target.value }))} className="tp-input" placeholder="Tenant phone" required />
          <div className="flex gap-2">
            <input type="number" min="1" max="168" value={assignmentForm.expiresInHours} onChange={(e) => setAssignmentForm((p) => ({ ...p, expiresInHours: e.target.value }))} className="tp-input w-20" placeholder="Hrs" />
            <button type="submit" className="tp-primary-btn whitespace-nowrap flex-1">Send Code</button>
          </div>
        </form>
        {assignmentMessage ? (
          <div className="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{assignmentMessage}</div>
        ) : null}
      </section>

      {/* ── Property Cards ── */}
      {rows.length === 0 ? (
        <div className="rounded-2xl border border-dashed border-gray-300 py-16 text-center text-sm text-gray-500">
          No properties yet. Click <strong>Add Property / Unit</strong> above to get started.
        </div>
      ) : null}

      <div className="space-y-8">
        {rows.map((property) => {
          const floors = groupByFloor(property.units);
          const floorKeys = [...floors.keys()];
          const currentFloor = activeFloor[property.id] ?? floorKeys[0] ?? '';
          const floorUnits = floors.get(currentFloor) ?? [];
          const occupied = property.units.filter((u) => u.status === 'OCCUPIED').length;
          const vacant = property.units.filter((u) => u.status === 'VACANT').length;
          const occupancy = property.units.length > 0 ? Math.round((occupied / property.units.length) * 100) : 0;
          const isExpanded = expandedPropertyId === property.id;

          return (
            <article key={property.id} className="overflow-hidden rounded-3xl border border-gray-200 bg-white shadow-lg">

              {/* Property Hero */}
              <div className="relative h-56 w-full overflow-hidden md:h-72">
                <img
                  src={mediaUrl(property.coverImageUrl) || FALLBACK_COVER}
                  alt={property.name}
                  className="h-full w-full object-cover transition duration-700 hover:scale-105"
                />
                {/* Gradient overlay */}
                <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent" />

                {/* Property info overlaid */}
                <div className="absolute bottom-0 left-0 right-0 p-5">
                  <div className="flex items-end justify-between gap-4">
                    <div>
                      <h2 className="text-2xl font-bold text-white drop-shadow">{property.name}</h2>
                      <p className="mt-0.5 text-sm text-slate-200">
                        📍 {property.addressLine}, {property.city}{property.state ? `, ${property.state}` : ''}, {property.country}
                      </p>
                    </div>
                    <div className="flex shrink-0 flex-wrap gap-2">
                      <span className="rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white shadow">{vacant} Vacant</span>
                      <span className="rounded-full bg-slate-700/80 px-3 py-1 text-xs font-semibold text-white shadow backdrop-blur-sm">{occupied} Occupied</span>
                    </div>
                  </div>
                </div>

                {/* Edit button */}
                <button
                  type="button"
                  onClick={() => startEditProperty(property)}
                  className="absolute right-4 top-4 rounded-lg bg-white/20 px-3 py-1.5 text-xs font-medium text-white backdrop-blur-sm hover:bg-white/30 transition"
                >
                  ✏️ Edit
                </button>
              </div>

              {/* Property meta bar */}
              <div className="flex flex-wrap items-center gap-4 border-b border-gray-100 bg-gray-50 px-5 py-3 text-sm">
                <span className="text-gray-600">
                  Landlord: <strong className="text-gray-900">{[property.landlord?.firstName, property.landlord?.lastName].filter(Boolean).join(' ') || property.landlord?.phoneNumber || '—'}</strong>
                </span>
                <span className="text-gray-600">
                  Units: <strong className="text-gray-900">{property.units.length}</strong>
                </span>
                {/* Occupancy bar */}
                <div className="ml-auto flex items-center gap-2">
                  <span className="text-xs text-gray-500">{occupancy}% occupied</span>
                  <div className="h-1.5 w-24 overflow-hidden rounded-full bg-gray-200">
                    <div className="h-full rounded-full bg-indigo-500 transition-all" style={{ width: `${occupancy}%` }} />
                  </div>
                </div>
                <button
                  type="button"
                  onClick={() => setExpandedPropertyId(isExpanded ? null : property.id)}
                  className="ml-2 rounded-lg border border-gray-300 px-3 py-1 text-xs font-medium text-gray-700 hover:bg-gray-100 transition"
                >
                  {isExpanded ? '▲ Collapse' : '▼ View Units'}
                </button>
              </div>

              {/* Units section — collapsible */}
              {isExpanded ? (
                <div className="p-5">
                  {property.units.length === 0 ? (
                    <div className="rounded-xl border border-dashed border-gray-300 py-8 text-center text-sm text-gray-500">
                      No units yet.{' '}
                      <button type="button" className="text-indigo-600 underline" onClick={() => { setActiveTab('addUnit'); setSelectedPropertyId(property.id); setShowAddPanel(true); }}>
                        Add a unit
                      </button>
                    </div>
                  ) : (
                    <>
                      {/* Floor tabs */}
                      {floorKeys.length > 1 ? (
                        <div className="mb-4 flex flex-wrap gap-2">
                          {floorKeys.map((floor) => (
                            <button
                              key={floor}
                              type="button"
                              onClick={() => setActiveFloor((prev) => ({ ...prev, [property.id]: floor }))}
                              className={`rounded-full px-4 py-1.5 text-xs font-semibold transition ${currentFloor === floor ? 'bg-slate-900 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'}`}
                            >
                              {floor}
                              <span className="ml-1.5 opacity-60">({floors.get(floor)?.length})</span>
                            </button>
                          ))}
                        </div>
                      ) : null}

                      {/* Unit grid */}
                      <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                        {floorUnits.map((unit, index) => {
                          const activeTenants = unit.tenants?.filter((t) => t.isActive !== false) ?? [];
                          const img = unitImg(unit, property, index);

                          return (
                            <div key={unit.id} className="group relative overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm transition hover:shadow-md hover:-translate-y-0.5">
                              {/* Unit photo */}
                              <div className="relative h-44 overflow-hidden">
                                <img
                                  src={img}
                                  alt={`Unit ${unit.unitNumber}`}
                                  className="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                                />
                                {/* Status dot */}
                                <div className="absolute left-3 top-3 flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-xs font-semibold shadow backdrop-blur-sm">
                                  <span className={`h-2 w-2 rounded-full ${statusColor(unit.status)}`} />
                                  {unit.status}
                                </div>
                                {/* Edit unit btn */}
                                <button
                                  type="button"
                                  onClick={() => startEditUnit(unit)}
                                  className="absolute right-2 top-2 rounded-lg bg-black/40 px-2 py-1 text-[11px] font-medium text-white opacity-0 transition group-hover:opacity-100 backdrop-blur-sm hover:bg-black/60"
                                >
                                  Edit
                                </button>
                              </div>

                              {/* Unit info */}
                              <div className="p-3.5">
                                <div className="flex items-start justify-between gap-2">
                                  <div>
                                    <h4 className="font-semibold text-gray-900">Unit {unit.unitNumber}</h4>
                                    <p className="text-xs text-gray-500">{unit.floor ? `Floor ${unit.floor}` : 'Ground Floor'}</p>
                                  </div>
                                  <p className="whitespace-nowrap text-sm font-bold text-indigo-700">
                                    KES {Number(unit.rentAmount).toLocaleString()}
                                    <span className="text-[10px] font-normal text-gray-400">/mo</span>
                                  </p>
                                </div>

                                {/* Occupant section */}
                                <div className="mt-3 min-h-[3rem]">
                                  {activeTenants.length > 0 ? (
                                    <div className="space-y-1.5">
                                      {activeTenants.map((t) => (
                                        <div key={t.id} className="flex items-center gap-2">
                                          {mediaUrl(t.user.profileImageUrl) ? (
                                            <img
                                              src={mediaUrl(t.user.profileImageUrl)!}
                                              alt={occupantName(t.user)}
                                              className="h-7 w-7 rounded-full object-cover border border-gray-200"
                                            />
                                          ) : (
                                            <div className="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">
                                              {(t.user.firstName?.[0] ?? t.user.phoneNumber[0]).toUpperCase()}
                                            </div>
                                          )}
                                          <div className="min-w-0">
                                            <p className="truncate text-xs font-medium text-gray-800">{occupantName(t.user)}</p>
                                            <p className="text-[10px] text-gray-400">{t.user.phoneNumber}</p>
                                          </div>
                                        </div>
                                      ))}
                                    </div>
                                  ) : (
                                    <div className={`flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs ${statusBadge(unit.status)}`}>
                                      {unit.status === 'VACANT' ? '🔓 Available to let' : unit.status === 'MAINTENANCE' ? '🔧 Under maintenance' : 'No active tenant'}
                                    </div>
                                  )}
                                </div>
                              </div>
                            </div>
                          );
                        })}
                      </div>
                    </>
                  )}
                </div>
              ) : null}
            </article>
          );
        })}
      </div>
    </main>
  );
}
