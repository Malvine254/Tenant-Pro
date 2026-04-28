"use client";

import { useSearchParams } from 'next/navigation';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { getSession, withDashboardMode } from '../../../lib/auth';
import { apiRequest } from '../../../lib/api';
import { getDemoDataset } from '../../../lib/demo-tenant-ops';
import Link from 'next/link';

type UserRow = {
  id: string;
  phoneNumber: string;
  email?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  role: 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';
  isActive: boolean;
};

type UnitRow = {
  id: string;
  unitNumber: string;
  floor?: string | null;
  rentAmount?: number | null;
  status: string;
};

type PropertyRow = {
  id: string;
  name: string;
  units: UnitRow[];
};

type TenantUnit = {
  id: string; // tenancy id
  isActive: boolean;
  moveInDate: string;
  unit: {
    id: string;
    unitNumber: string;
    floor?: string | null;
    rentAmount?: number | null;
    property: { id: string; name: string };
  };
};

function tenantDisplayName(t: UserRow) {
  return [t.firstName, t.lastName].filter(Boolean).join(' ') || t.phoneNumber;
}

export default function TenantsPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';

  const [rows, setRows] = useState<UserRow[]>([]);
  const [error, setError] = useState<string | null>(null);

  // modal state
  const [modalTenant, setModalTenant] = useState<UserRow | null>(null);
  const [tenantUnits, setTenantUnits] = useState<TenantUnit[]>([]);
  const [unitsLoading, setUnitsLoading] = useState(false);
  const [properties, setProperties] = useState<PropertyRow[]>([]);
  const [selectedPropertyId, setSelectedPropertyId] = useState('');
  const [selectedUnitId, setSelectedUnitId] = useState('');
  const [assigning, setAssigning] = useState(false);
  const [modalError, setModalError] = useState<string | null>(null);
  const [modalSuccess, setModalSuccess] = useState<string | null>(null);

  useEffect(() => {
    const run = async () => {
      try {
        if (isDemoMode) {
          setRows(getDemoDataset().users as UserRow[]);
          return;
        }
        const session = getSession();
        if (!session) return;
        const users = await apiRequest<UserRow[]>('/users', session.accessToken);
        setRows(users);
      } catch (e) {
        setError(e instanceof Error ? e.message : 'Failed to load tenants');
      }
    };
    void run();
  }, [isDemoMode]);

  const tenants = useMemo(() => rows.filter((r) => r.role === 'TENANT'), [rows]);

  const stats = useMemo(() => ({
    total: tenants.length,
    active: tenants.filter((t) => t.isActive).length,
    inactive: tenants.filter((t) => !t.isActive).length,
  }), [tenants]);

  const openModal = useCallback(async (tenant: UserRow) => {
    setModalTenant(tenant);
    setModalError(null);
    setModalSuccess(null);
    setSelectedPropertyId('');
    setSelectedUnitId('');

    if (isDemoMode) return;

    const session = getSession();
    if (!session) return;

    // Load tenant's current units + all properties in parallel
    setUnitsLoading(true);
    try {
      const [units, props] = await Promise.all([
        apiRequest<TenantUnit[]>(`/users/${tenant.id}/units`, session.accessToken),
        apiRequest<PropertyRow[]>('/properties', session.accessToken),
      ]);
      setTenantUnits(units);
      setProperties(props);
    } catch (e) {
      setModalError(e instanceof Error ? e.message : 'Failed to load data');
    } finally {
      setUnitsLoading(false);
    }
  }, [isDemoMode]);

  const closeModal = () => {
    setModalTenant(null);
    setTenantUnits([]);
    setProperties([]);
    setModalError(null);
    setModalSuccess(null);
  };

  const handleAssign = async () => {
    if (!selectedUnitId || !modalTenant) return;
    const session = getSession();
    if (!session) return;

    setAssigning(true);
    setModalError(null);
    setModalSuccess(null);
    try {
      const result = await apiRequest<TenantUnit>(
        `/users/${modalTenant.id}/assign-unit`,
        session.accessToken,
        { method: 'POST', body: JSON.stringify({ unitId: selectedUnitId }) },
      );
      setTenantUnits((prev) => [result, ...prev]);
      setSelectedPropertyId('');
      setSelectedUnitId('');
      setModalSuccess(`Unit assigned successfully`);
    } catch (e) {
      setModalError(e instanceof Error ? e.message : 'Failed to assign unit');
    } finally {
      setAssigning(false);
    }
  };

  const handleRemove = async (unitId: string) => {
    if (!modalTenant) return;
    const session = getSession();
    if (!session) return;

    setModalError(null);
    try {
      await apiRequest(
        `/users/${modalTenant.id}/units/${unitId}`,
        session.accessToken,
        { method: 'DELETE' },
      );
      setTenantUnits((prev) => prev.filter((tu) => tu.unit.id !== unitId));
      setModalSuccess('Unit removed successfully');
    } catch (e) {
      setModalError(e instanceof Error ? e.message : 'Failed to remove unit');
    }
  };

  const selectedPropertyUnits = useMemo(() => {
    const prop = properties.find((p) => p.id === selectedPropertyId);
    if (!prop) return [];
    const assignedUnitIds = new Set(tenantUnits.map((tu) => tu.unit.id));
    return prop.units.filter((u) => u.status !== 'MAINTENANCE' && !assignedUnitIds.has(u.id));
  }, [selectedPropertyId, properties, tenantUnits]);

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-cyan-600 via-sky-600 to-indigo-700 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Tenant Directory</h2>
        <p className="mt-2 max-w-2xl text-sm text-cyan-50">Manage tenant unit assignments across your properties.</p>
        <div className="mt-4 flex flex-wrap gap-3 text-xs">
          <span className="rounded-full bg-white/15 px-3 py-1.5">{stats.total} tenants</span>
          <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-100">{stats.active} active</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{stats.inactive} inactive</span>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        {error ? <div className="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-600">{error}</div> : null}

        <div className="overflow-x-auto rounded-2xl border border-gray-200 bg-white shadow-sm">
          <table className="min-w-full text-sm">
            <thead className="bg-gray-50 text-left text-gray-700">
              <tr>
                <th className="px-4 py-3">Name</th>
                <th className="px-4 py-3">Phone</th>
                <th className="px-4 py-3">Email</th>
                <th className="px-4 py-3">Status</th>
                <th className="px-4 py-3">Actions</th>
              </tr>
            </thead>
            <tbody>
              {tenants.map((tenant) => (
                <tr key={tenant.id} className="border-t border-gray-100">
                  <td className="px-4 py-3 font-medium text-gray-900">{tenantDisplayName(tenant)}</td>
                  <td className="px-4 py-3">{tenant.phoneNumber}</td>
                  <td className="px-4 py-3">{tenant.email ?? '-'}</td>
                  <td className="px-4 py-3">
                    <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${tenant.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                      {tenant.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </td>
                  <td className="px-4 py-3">
                    <div className="flex flex-wrap gap-2">
                      <button
                        onClick={() => void openModal(tenant)}
                        className="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700"
                      >
                        Manage Units
                      </button>
                      <Link
                        href={withDashboardMode(`/dashboard/messages?tenantId=${tenant.id}&tenantName=${encodeURIComponent(tenantDisplayName(tenant))}`, isDemoMode)}
                        className="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-slate-800"
                      >
                        Message
                      </Link>
                    </div>
                  </td>
                </tr>
              ))}
              {tenants.length === 0 && (
                <tr>
                  <td colSpan={5} className="px-4 py-8 text-center text-gray-400">No tenants found</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      </section>

      {/* Manage Units Modal */}
      {modalTenant ? (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
          <div className="w-full max-w-lg rounded-2xl bg-white shadow-2xl">
            <div className="flex items-center justify-between border-b border-gray-100 px-6 py-4">
              <div>
                <h3 className="text-lg font-semibold text-gray-900">Manage Units</h3>
                <p className="text-sm text-gray-500">{tenantDisplayName(modalTenant)}</p>
              </div>
              <button onClick={closeModal} className="rounded-lg p-2 text-gray-400 hover:bg-gray-100 hover:text-gray-600">✕</button>
            </div>

            <div className="max-h-[70vh] overflow-y-auto p-6 space-y-5">
              {modalError ? <div className="rounded-lg bg-red-50 px-4 py-2 text-sm text-red-600">{modalError}</div> : null}
              {modalSuccess ? <div className="rounded-lg bg-emerald-50 px-4 py-2 text-sm text-emerald-700">{modalSuccess}</div> : null}

              {/* Current assignments */}
              <div>
                <h4 className="mb-2 text-sm font-semibold text-gray-700">Current Assignments</h4>
                {unitsLoading ? (
                  <p className="text-sm text-gray-400">Loading…</p>
                ) : tenantUnits.length === 0 ? (
                  <p className="rounded-lg bg-gray-50 px-4 py-3 text-sm text-gray-400">No units assigned yet</p>
                ) : (
                  <ul className="space-y-2">
                    {tenantUnits.map((tu) => (
                      <li key={tu.unit.id} className="flex items-center justify-between rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                        <div>
                          <p className="text-sm font-medium text-gray-900">
                            {tu.unit.property.name} — Unit {tu.unit.unitNumber}
                            {tu.unit.floor ? <span className="ml-1 text-gray-500">(Floor {tu.unit.floor})</span> : null}
                          </p>
                          {tu.unit.rentAmount != null ? (
                            <p className="text-xs text-gray-500">KES {tu.unit.rentAmount.toLocaleString()} / month</p>
                          ) : null}
                        </div>
                        <button
                          onClick={() => void handleRemove(tu.unit.id)}
                          className="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-100"
                        >
                          Remove
                        </button>
                      </li>
                    ))}
                  </ul>
                )}
              </div>

              {/* Assign new unit */}
              {!isDemoMode ? (
                <div className="rounded-xl border border-indigo-100 bg-indigo-50 p-4 space-y-3">
                  <h4 className="text-sm font-semibold text-indigo-700">Assign a Unit</h4>

                  <div>
                    <label className="mb-1 block text-xs font-medium text-gray-600">Property</label>
                    <select
                      value={selectedPropertyId}
                      onChange={(e) => { setSelectedPropertyId(e.target.value); setSelectedUnitId(''); }}
                      className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                    >
                      <option value="">Select property…</option>
                      {properties.map((p) => (
                        <option key={p.id} value={p.id}>{p.name}</option>
                      ))}
                    </select>
                  </div>

                  {selectedPropertyId ? (
                    <div>
                      <label className="mb-1 block text-xs font-medium text-gray-600">Unit</label>
                      <select
                        value={selectedUnitId}
                        onChange={(e) => setSelectedUnitId(e.target.value)}
                        className="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none"
                      >
                        <option value="">Select unit…</option>
                        {selectedPropertyUnits.map((u) => (
                          <option key={u.id} value={u.id}>
                            Unit {u.unitNumber}{u.floor ? ` · Floor ${u.floor}` : ''}{u.rentAmount ? ` · KES ${u.rentAmount.toLocaleString()}` : ''} ({u.status})
                          </option>
                        ))}
                        {selectedPropertyUnits.length === 0 && (
                          <option disabled>No available units</option>
                        )}
                      </select>
                    </div>
                  ) : null}

                  <button
                    onClick={() => void handleAssign()}
                    disabled={!selectedUnitId || assigning}
                    className="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    {assigning ? 'Assigning…' : 'Assign Unit'}
                  </button>
                </div>
              ) : null}
            </div>
          </div>
        </div>
      ) : null}
    </main>
  );
}
