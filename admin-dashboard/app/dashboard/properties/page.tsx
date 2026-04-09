"use client";

import { useSearchParams } from 'next/navigation';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { apiRequest } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset, saveDemoDataset } from '../../../lib/demo-tenant-ops';

const DEFAULT_ROOM_IMAGE = 'https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?auto=format&fit=crop&w=900&q=80';

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
  units: Array<{
    id: string;
    unitNumber: string;
    floor?: string | null;
    rentAmount: number | string;
    status: string;
    imageUrls?: string[] | null;
  }>;
};

type LandlordRow = {
  id: string;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  firstName?: string | null;
  lastName?: string | null;
  phoneNumber: string;
};

export default function PropertiesPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [rows, setRows] = useState<PropertyRow[]>([]);
  const [landlords, setLandlords] = useState<LandlordRow[]>([]);
  const [tenants, setTenants] = useState<LandlordRow[]>([]);
  const [selectedPropertyId, setSelectedPropertyId] = useState<string>('');
  const [editingPropertyId, setEditingPropertyId] = useState<string | null>(null);
  const [editingUnitId, setEditingUnitId] = useState<string | null>(null);
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
    value
      .split('\n')
      .map((item) => item.trim())
      .filter(Boolean);

  const hydrateWorkspace = (properties: PropertyRow[], users: LandlordRow[]) => {
    setRows(properties);
    const landlordRows = users.filter((user) => user.role === 'LANDLORD');
    const tenantRows = users.filter((user) => user.role === 'TENANT');
    setLandlords(landlordRows);
    setTenants(tenantRows);

    if (!selectedPropertyId && properties[0]) {
      setSelectedPropertyId(properties[0].id);
    }
    if (!propertyForm.landlordId && landlordRows[0]) {
      setPropertyForm((prev) => ({ ...prev, landlordId: landlordRows[0].id }));
    }
    if (!assignmentForm.tenantId && tenantRows[0]) {
      setAssignmentForm((prev) => ({ ...prev, tenantId: tenantRows[0].id, phoneNumber: tenantRows[0].phoneNumber }));
    }
    if (!assignmentForm.propertyId && properties[0]) {
      const firstUnit = properties[0].units.find((unit) => unit.status !== 'OCCUPIED') ?? properties[0].units[0];
      setAssignmentForm((prev) => ({ ...prev, propertyId: properties[0].id, unitId: firstUnit?.id ?? prev.unitId }));
    }
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
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to load properties');
    }
  };

  useEffect(() => {
    void load();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isDemoMode]);

  const submitProperty = async (event: FormEvent) => {
    event.preventDefault();

    try {
      setError(null);

      if (isDemoMode) {
        const dataset = getDemoDataset();
        const landlord = landlords.find((item) => item.id === propertyForm.landlordId);
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
          landlord: {
            firstName: landlord?.firstName,
            lastName: landlord?.lastName,
            phoneNumber: landlord?.phoneNumber,
          },
          units: [],
        };
        dataset.properties.unshift(newProperty);
        saveDemoDataset(dataset);
        setPropertyForm((prev) => ({
          ...prev,
          name: '',
          description: '',
          coverImageUrl: '',
          addressLine: '',
          city: '',
          state: '',
        }));
        setSelectedPropertyId(newProperty.id);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest('/properties', session.accessToken, {
        method: 'POST',
        body: JSON.stringify(propertyForm),
      });
      setPropertyForm((prev) => ({
        ...prev,
        name: '',
        description: '',
        coverImageUrl: '',
        addressLine: '',
        city: '',
        state: '',
      }));
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to create property');
    }
  };

  const submitUnit = async (event: FormEvent) => {
    event.preventDefault();
    if (!selectedPropertyId) return;

    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.properties = dataset.properties.map((property) =>
          property.id === selectedPropertyId
            ? {
                ...property,
                units: [
                  ...property.units,
                  {
                    id: `demo-unit-${Date.now()}`,
                    unitNumber: unitForm.unitNumber,
                    floor: unitForm.floor || null,
                    rentAmount: Number(unitForm.rentAmount),
                    status: unitForm.status,
                    imageUrls: parseImageUrls(unitForm.imageUrls),
                  },
                ],
              }
            : property,
        );
        saveDemoDataset(dataset);
        setUnitForm({ unitNumber: '', floor: '', rentAmount: '', status: 'VACANT', imageUrls: '' });
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest(`/properties/${selectedPropertyId}/units`, session.accessToken, {
        method: 'POST',
        body: JSON.stringify({
          unitNumber: unitForm.unitNumber,
          floor: unitForm.floor || undefined,
          rentAmount: Number(unitForm.rentAmount),
          status: unitForm.status,
          imageUrls: parseImageUrls(unitForm.imageUrls),
        }),
      });
      setUnitForm({ unitNumber: '', floor: '', rentAmount: '', status: 'VACANT', imageUrls: '' });
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to add unit');
    }
  };

  const startEditProperty = (property: PropertyRow) => {
    setEditingPropertyId(property.id);
    setPropertyEditForm({
      landlordId: property.landlordId,
      name: property.name,
      description: property.description ?? '',
      coverImageUrl: property.coverImageUrl ?? '',
      addressLine: property.addressLine,
      city: property.city,
      state: property.state ?? '',
      country: property.country,
    });
  };

  const submitEditProperty = async (event: FormEvent) => {
    event.preventDefault();
    if (!editingPropertyId) return;

    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        const landlord = landlords.find((item) => item.id === propertyEditForm.landlordId);
        dataset.properties = dataset.properties.map((property) =>
          property.id === editingPropertyId
            ? {
                ...property,
                ...propertyEditForm,
                description: propertyEditForm.description || null,
                coverImageUrl: propertyEditForm.coverImageUrl || null,
                state: propertyEditForm.state || null,
                landlord: {
                  firstName: landlord?.firstName,
                  lastName: landlord?.lastName,
                  phoneNumber: landlord?.phoneNumber,
                },
              }
            : property,
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
        body: JSON.stringify({
          ...propertyEditForm,
          coverImageUrl: propertyEditForm.coverImageUrl || undefined,
          description: propertyEditForm.description || undefined,
          state: propertyEditForm.state || undefined,
        }),
      });
      setEditingPropertyId(null);
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to update property');
    }
  };

  const startEditUnit = (unit: PropertyRow['units'][number]) => {
    setEditingUnitId(unit.id);
    setUnitEditForm({
      unitNumber: unit.unitNumber,
      floor: unit.floor ?? '',
      rentAmount: String(unit.rentAmount),
      status: unit.status,
      imageUrls: (unit.imageUrls ?? []).join('\n'),
    });
  };

  const submitEditUnit = async (event: FormEvent) => {
    event.preventDefault();
    if (!editingUnitId) return;

    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.properties = dataset.properties.map((property) => ({
          ...property,
          units: property.units.map((unit) =>
            unit.id === editingUnitId
              ? {
                  ...unit,
                  unitNumber: unitEditForm.unitNumber,
                  floor: unitEditForm.floor || null,
                  rentAmount: Number(unitEditForm.rentAmount),
                  status: unitEditForm.status,
                  imageUrls: parseImageUrls(unitEditForm.imageUrls),
                }
              : unit,
          ),
        }));
        saveDemoDataset(dataset);
        setEditingUnitId(null);
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest(`/properties/units/${editingUnitId}`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({
          unitNumber: unitEditForm.unitNumber,
          floor: unitEditForm.floor || undefined,
          rentAmount: Number(unitEditForm.rentAmount),
          status: unitEditForm.status,
          imageUrls: parseImageUrls(unitEditForm.imageUrls),
        }),
      });
      setEditingUnitId(null);
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to update unit');
    }
  };

  const assignmentUnits = useMemo(() => {
    const property = rows.find((item) => item.id === assignmentForm.propertyId);
    return property?.units.filter((unit) => unit.status !== 'OCCUPIED') ?? [];
  }, [assignmentForm.propertyId, rows]);

  const submitAssignment = async (event: FormEvent) => {
    event.preventDefault();

    try {
      setAssignmentMessage(null);

      if (isDemoMode) {
        const dataset = getDemoDataset();
        const invitationCode = `DMO-${Math.floor(100000 + Math.random() * 900000)}`;
        dataset.properties = dataset.properties.map((property) =>
          property.id === assignmentForm.propertyId
            ? {
                ...property,
                units: property.units.map((unit) =>
                  unit.id === assignmentForm.unitId ? { ...unit, status: 'OCCUPIED' } : unit,
                ),
              }
            : property,
        );
        saveDemoDataset(dataset);
        setAssignmentMessage(
          `Assignment code ${invitationCode} created for this demo session. The selected unit is now marked as occupied for testing.`,
        );
        hydrateWorkspace(dataset.properties as PropertyRow[], dataset.users as LandlordRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      const invitation = await apiRequest<{ code: string; expiresAt: string }>(
        '/invitations',
        session.accessToken,
        {
          method: 'POST',
          body: JSON.stringify({
            propertyId: assignmentForm.propertyId,
            unitId: assignmentForm.unitId,
            phoneNumber: assignmentForm.phoneNumber,
            expiresInHours: Number(assignmentForm.expiresInHours || 72),
            sentVia: 'ADMIN_DASHBOARD',
          }),
        },
      );

      setAssignmentMessage(
        `Assignment code ${invitation.code} created. The tenant should open Tenant App → Account Settings → Link apartment and enter this code.`,
      );
      await load();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to assign room');
    }
  };

  const totalUnits = rows.reduce((acc, property) => acc + property.units.length, 0);
  const vacantUnits = rows.reduce(
    (acc, property) => acc + property.units.filter((unit) => unit.status === 'VACANT').length,
    0,
  );

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="relative shrink-0 overflow-hidden rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-900 px-6 py-8 text-white shadow-xl">
        <div className="relative z-10 space-y-3">
          <h2 className="text-3xl font-bold tracking-tight">Property Studio</h2>
          <p className="max-w-2xl text-sm text-slate-200">Modern property operations: create listings, edit units, switch vacancy status, and showcase room photos in one place.</p>
          <div className="flex flex-wrap gap-3 pt-2 text-xs">
            <span className="rounded-full bg-white/15 px-3 py-1.5">{rows.length} properties</span>
            <span className="rounded-full bg-white/15 px-3 py-1.5">{totalUnits} total units</span>
            <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-100">{vacantUnits} vacant</span>
          </div>
        </div>
        <div className="pointer-events-none absolute -right-10 -top-10 h-44 w-44 rounded-full bg-indigo-300/20 blur-3xl" />
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? (
            <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
          ) : null}

          <section className="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
              <button
                type="button"
                onClick={() => setActiveTab('createProperty')}
                className={`rounded-lg px-4 py-2 text-sm font-medium transition ${
                  activeTab === 'createProperty'
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                Create Property
              </button>
              <button
                type="button"
                onClick={() => setActiveTab('addUnit')}
                className={`rounded-lg px-4 py-2 text-sm font-medium transition ${
                  activeTab === 'addUnit'
                    ? 'bg-white text-slate-900 shadow-sm'
                    : 'text-gray-600 hover:text-gray-900'
                }`}
              >
                Add Unit
              </button>
            </div>

            {activeTab === 'createProperty' ? (
              <form onSubmit={submitProperty} className="space-y-3">
                <h3 className="text-base font-semibold">Create Property</h3>
                <select
                  value={propertyForm.landlordId}
                  onChange={(event) => setPropertyForm({ ...propertyForm, landlordId: event.target.value })}
                  className="tp-select"
                  required
                >
                  <option value="">Select landlord</option>
                  {landlords.map((landlord) => (
                    <option key={landlord.id} value={landlord.id}>
                      {([landlord.firstName, landlord.lastName].filter(Boolean).join(' ') || landlord.phoneNumber)}
                    </option>
                  ))}
                </select>
                <input value={propertyForm.name} onChange={(event) => setPropertyForm({ ...propertyForm, name: event.target.value })} className="tp-input" placeholder="Property name" required />
                <textarea value={propertyForm.description} onChange={(event) => setPropertyForm({ ...propertyForm, description: event.target.value })} className="tp-textarea" placeholder="Description" rows={3} />
                <input value={propertyForm.coverImageUrl} onChange={(event) => setPropertyForm({ ...propertyForm, coverImageUrl: event.target.value })} className="tp-input" placeholder="Cover image URL" />
                <input value={propertyForm.addressLine} onChange={(event) => setPropertyForm({ ...propertyForm, addressLine: event.target.value })} className="tp-input" placeholder="Address line" required />
                <div className="grid gap-3 md:grid-cols-3">
                  <input value={propertyForm.city} onChange={(event) => setPropertyForm({ ...propertyForm, city: event.target.value })} className="tp-input" placeholder="City" required />
                  <input value={propertyForm.state} onChange={(event) => setPropertyForm({ ...propertyForm, state: event.target.value })} className="tp-input" placeholder="State" />
                  <input value={propertyForm.country} onChange={(event) => setPropertyForm({ ...propertyForm, country: event.target.value })} className="tp-input" placeholder="Country" required />
                </div>
                <button type="submit" className="tp-primary-btn">Create Property</button>
              </form>
            ) : (
              <form onSubmit={submitUnit} className="space-y-3">
                <h3 className="text-base font-semibold">Add Unit</h3>
                <select
                  value={selectedPropertyId}
                  onChange={(event) => setSelectedPropertyId(event.target.value)}
                  className="tp-select"
                  required
                >
                  <option value="">Select property</option>
                  {rows.map((property) => (
                    <option key={property.id} value={property.id}>
                      {property.name}
                    </option>
                  ))}
                </select>
                <div className="grid gap-3 md:grid-cols-2">
                  <input value={unitForm.unitNumber} onChange={(event) => setUnitForm({ ...unitForm, unitNumber: event.target.value })} className="tp-input" placeholder="Unit number" required />
                  <input value={unitForm.floor} onChange={(event) => setUnitForm({ ...unitForm, floor: event.target.value })} className="tp-input" placeholder="Floor" />
                </div>
                <div className="grid gap-3 md:grid-cols-2">
                  <input type="number" step="0.01" value={unitForm.rentAmount} onChange={(event) => setUnitForm({ ...unitForm, rentAmount: event.target.value })} className="tp-input" placeholder="Rent amount" required />
                  <select value={unitForm.status} onChange={(event) => setUnitForm({ ...unitForm, status: event.target.value })} className="tp-select">
                    <option value="VACANT">VACANT</option>
                    <option value="OCCUPIED">OCCUPIED</option>
                    <option value="MAINTENANCE">MAINTENANCE</option>
                  </select>
                </div>
                <textarea value={unitForm.imageUrls} onChange={(event) => setUnitForm({ ...unitForm, imageUrls: event.target.value })} className="tp-textarea" placeholder="Room image URLs, one per line" rows={4} />
                <button type="submit" className="tp-primary-btn">Add Unit</button>
              </form>
            )}
          </section>

          <section className="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">Assign Room to Tenant</h3>
              <p className="mt-1 text-sm text-gray-600">
                Pick a tenant and an available unit, then generate an assignment code so the tenant can link their apartment and see their landlord details in the app.
              </p>
            </div>

            <form onSubmit={submitAssignment} className="grid gap-3 md:grid-cols-2 xl:grid-cols-5">
              <select
                value={assignmentForm.tenantId}
                onChange={(event) => {
                  const tenant = tenants.find((item) => item.id === event.target.value);
                  setAssignmentForm((prev) => ({
                    ...prev,
                    tenantId: event.target.value,
                    phoneNumber: tenant?.phoneNumber ?? prev.phoneNumber,
                  }));
                }}
                className="tp-select"
                required
              >
                <option value="">Select tenant</option>
                {tenants.map((tenant) => (
                  <option key={tenant.id} value={tenant.id}>
                    {[tenant.firstName, tenant.lastName].filter(Boolean).join(' ') || tenant.phoneNumber}
                  </option>
                ))}
              </select>

              <select
                value={assignmentForm.propertyId}
                onChange={(event) => {
                  const property = rows.find((item) => item.id === event.target.value);
                  const firstUnit = property?.units.find((unit) => unit.status !== 'OCCUPIED') ?? property?.units[0];
                  setAssignmentForm((prev) => ({
                    ...prev,
                    propertyId: event.target.value,
                    unitId: firstUnit?.id ?? '',
                  }));
                }}
                className="tp-select"
                required
              >
                <option value="">Select property</option>
                {rows.map((property) => (
                  <option key={property.id} value={property.id}>{property.name}</option>
                ))}
              </select>

              <select
                value={assignmentForm.unitId}
                onChange={(event) => setAssignmentForm((prev) => ({ ...prev, unitId: event.target.value }))}
                className="tp-select"
                required
              >
                <option value="">Select unit</option>
                {assignmentUnits.map((unit) => (
                  <option key={unit.id} value={unit.id}>
                    {unit.unitNumber} • {unit.status} • KES {Number(unit.rentAmount).toLocaleString()}
                  </option>
                ))}
              </select>

              <input
                value={assignmentForm.phoneNumber}
                onChange={(event) => setAssignmentForm((prev) => ({ ...prev, phoneNumber: event.target.value }))}
                className="tp-input"
                placeholder="Tenant phone"
                required
              />

              <div className="flex gap-2">
                <input
                  type="number"
                  min="1"
                  max="168"
                  value={assignmentForm.expiresInHours}
                  onChange={(event) => setAssignmentForm((prev) => ({ ...prev, expiresInHours: event.target.value }))}
                  className="tp-input"
                  placeholder="Hours"
                />
                <button type="submit" className="tp-primary-btn whitespace-nowrap">Assign Room</button>
              </div>
            </form>

            {assignmentMessage ? (
              <div className="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {assignmentMessage}
              </div>
            ) : null}
          </section>

          {editingPropertyId ? (
            <form onSubmit={submitEditProperty} className="tp-form-panel !space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="tp-form-title">Edit Property</h3>
                <button type="button" onClick={() => setEditingPropertyId(null)} className="tp-secondary-btn !rounded-md !px-2.5 !py-1">Cancel</button>
              </div>
              <select value={propertyEditForm.landlordId} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, landlordId: event.target.value })} className="tp-select" required>
                {landlords.map((landlord) => (
                  <option key={landlord.id} value={landlord.id}>
                    {([landlord.firstName, landlord.lastName].filter(Boolean).join(' ') || landlord.phoneNumber)}
                  </option>
                ))}
              </select>
              <input value={propertyEditForm.name} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, name: event.target.value })} className="tp-input" placeholder="Property name" required />
              <textarea value={propertyEditForm.description} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, description: event.target.value })} className="tp-textarea" rows={3} placeholder="Description" />
              <input value={propertyEditForm.coverImageUrl} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, coverImageUrl: event.target.value })} className="tp-input" placeholder="Cover image URL" />
              <input value={propertyEditForm.addressLine} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, addressLine: event.target.value })} className="tp-input" placeholder="Address line" required />
              <div className="grid gap-3 md:grid-cols-3">
                <input value={propertyEditForm.city} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, city: event.target.value })} className="tp-input" placeholder="City" required />
                <input value={propertyEditForm.state} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, state: event.target.value })} className="tp-input" placeholder="State" />
                <input value={propertyEditForm.country} onChange={(event) => setPropertyEditForm({ ...propertyEditForm, country: event.target.value })} className="tp-input" placeholder="Country" required />
              </div>
              <button type="submit" className="tp-primary-btn">Save Property Changes</button>
            </form>
          ) : null}

          {editingUnitId ? (
            <form onSubmit={submitEditUnit} className="tp-form-panel !space-y-3">
              <div className="flex items-center justify-between">
                <h3 className="tp-form-title">Edit Unit</h3>
                <button type="button" onClick={() => setEditingUnitId(null)} className="tp-secondary-btn !rounded-md !px-2.5 !py-1">Cancel</button>
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                <input value={unitEditForm.unitNumber} onChange={(event) => setUnitEditForm({ ...unitEditForm, unitNumber: event.target.value })} className="tp-input" placeholder="Unit number" required />
                <input value={unitEditForm.floor} onChange={(event) => setUnitEditForm({ ...unitEditForm, floor: event.target.value })} className="tp-input" placeholder="Floor" />
              </div>
              <div className="grid gap-3 md:grid-cols-2">
                <input type="number" step="0.01" value={unitEditForm.rentAmount} onChange={(event) => setUnitEditForm({ ...unitEditForm, rentAmount: event.target.value })} className="tp-input" placeholder="Rent amount" required />
                <select value={unitEditForm.status} onChange={(event) => setUnitEditForm({ ...unitEditForm, status: event.target.value })} className="tp-select">
                  <option value="VACANT">VACANT</option>
                  <option value="OCCUPIED">OCCUPIED</option>
                  <option value="MAINTENANCE">MAINTENANCE</option>
                </select>
              </div>
              <textarea value={unitEditForm.imageUrls} onChange={(event) => setUnitEditForm({ ...unitEditForm, imageUrls: event.target.value })} className="tp-textarea" rows={4} placeholder="Room image URLs, one per line" />
              <button type="submit" className="tp-primary-btn">Save Unit Changes</button>
            </form>
          ) : null}

          <div className="grid gap-6">
            {rows.map((property) => {
              const vacantCount = property.units.filter((unit) => unit.status === 'VACANT').length;

              return (
                <section key={property.id} className="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-md transition hover:shadow-lg">
                  <div className="grid gap-0 lg:grid-cols-[320px_1fr]">
                    <div className="h-64 bg-gray-100">
                      <img
                        src={property.coverImageUrl || DEFAULT_ROOM_IMAGE}
                        alt={property.name}
                        className="h-full w-full object-cover"
                      />
                    </div>
                    <div className="space-y-4 p-5">
                      <div className="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                        <div>
                          <h3 className="text-2xl font-semibold text-gray-900">{property.name}</h3>
                          <p className="text-sm text-gray-600">{property.addressLine}, {property.city}{property.state ? `, ${property.state}` : ''}, {property.country}</p>
                          {property.description ? <p className="mt-2 text-sm text-gray-700">{property.description}</p> : null}
                          <p className="mt-2 text-xs text-gray-500">Landlord: {([property.landlord?.firstName, property.landlord?.lastName].filter(Boolean).join(' ') || property.landlord?.phoneNumber || 'N/A')}</p>
                        </div>
                        <div className="flex flex-wrap gap-2">
                          <span className="rounded-full bg-green-100 px-3 py-1 text-xs font-medium text-green-700">{vacantCount} vacant</span>
                          <span className="rounded-full bg-blue-100 px-3 py-1 text-xs font-medium text-blue-700">{property.units.length} total units</span>
                          <button type="button" onClick={() => startEditProperty(property)} className="tp-secondary-btn !px-3 !py-1.5">Edit Property</button>
                        </div>
                      </div>

                      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {property.units.length === 0 ? (
                          <div className="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-500">No units yet.</div>
                        ) : (
                          property.units.map((unit) => (
                            <article key={unit.id} className="rounded-xl border border-gray-200 p-3 shadow-sm">
                              <img
                                src={unit.imageUrls?.[0] || property.coverImageUrl || DEFAULT_ROOM_IMAGE}
                                alt={`Unit ${unit.unitNumber}`}
                                className="mb-3 h-40 w-full rounded-lg object-cover"
                              />
                              <div className="flex items-start justify-between gap-3">
                                <div>
                                  <h4 className="font-semibold text-gray-900">Unit {unit.unitNumber}</h4>
                                  <p className="text-sm text-gray-600">Floor: {unit.floor || 'N/A'}</p>
                                  <p className="text-sm text-gray-700">KES {Number(unit.rentAmount).toLocaleString()}</p>
                                </div>
                                <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${unit.status === 'VACANT' ? 'bg-emerald-100 text-emerald-700' : unit.status === 'MAINTENANCE' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'}`}>{unit.status}</span>
                              </div>
                              <button type="button" onClick={() => startEditUnit(unit)} className="tp-secondary-btn mt-3 w-full">Edit Unit / Vacancy / Photos</button>
                            </article>
                          ))
                        )}
                      </div>
                    </div>
                  </div>
                </section>
              );
            })}
          </div>
        </div>
      </section>
    </main>
  );
}
