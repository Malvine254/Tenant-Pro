"use client";

import { useSearchParams } from 'next/navigation';
import { FormEvent, useEffect, useMemo, useState } from 'react';
import { apiRequest } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset, saveDemoDataset } from '../../../lib/demo-tenant-ops';

type RoleName = 'LANDLORD' | 'TENANT' | 'ADMIN' | 'CARETAKER';

type UserRow = {
  id: string;
  phoneNumber: string;
  email?: string | null;
  firstName?: string | null;
  lastName?: string | null;
  role: RoleName;
  isActive: boolean;
  createdAt?: string;
};

const roleOptions: RoleName[] = ['ADMIN', 'LANDLORD', 'TENANT', 'CARETAKER'];

export default function UsersPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const [rows, setRows] = useState<UserRow[]>([]);
  const [roleFilter, setRoleFilter] = useState<'ALL' | RoleName>('ALL');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [createForm, setCreateForm] = useState({
    phoneNumber: '',
    email: '',
    firstName: '',
    lastName: '',
    password: '',
    role: 'TENANT' as RoleName,
  });

  const loadUsers = async () => {
    try {
      setLoading(true);
      setError(null);

      if (isDemoMode) {
        setRows(getDemoDataset().users as UserRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      const users = await apiRequest<UserRow[]>('/users', session.accessToken);
      setRows(users);
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to load users');
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    void loadUsers();
  }, [isDemoMode]);

  const filteredRows = useMemo(() => {
    return roleFilter === 'ALL' ? rows : rows.filter((row) => row.role === roleFilter);
  }, [rows, roleFilter]);

  const counts = useMemo(() => {
    return {
      total: rows.length,
      active: rows.filter((row) => row.isActive).length,
      admins: rows.filter((row) => row.role === 'ADMIN').length,
      caretakers: rows.filter((row) => row.role === 'CARETAKER').length,
    };
  }, [rows]);

  const createUser = async (event: FormEvent) => {
    event.preventDefault();

    try {
      setError(null);

      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.users.unshift({
          id: `demo-user-${Date.now()}`,
          phoneNumber: createForm.phoneNumber,
          email: createForm.email || null,
          firstName: createForm.firstName || null,
          lastName: createForm.lastName || null,
          role: createForm.role,
          isActive: true,
          createdAt: new Date().toISOString(),
        });
        saveDemoDataset(dataset);
        setRows(dataset.users as UserRow[]);
        setCreateForm({
          phoneNumber: '',
          email: '',
          firstName: '',
          lastName: '',
          password: '',
          role: 'TENANT',
        });
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest('/users', session.accessToken, {
        method: 'POST',
        body: JSON.stringify({
          ...createForm,
          email: createForm.email || undefined,
          firstName: createForm.firstName || undefined,
          lastName: createForm.lastName || undefined,
          password: createForm.password || undefined,
        }),
      });
      setCreateForm({
        phoneNumber: '',
        email: '',
        firstName: '',
        lastName: '',
        password: '',
        role: 'TENANT',
      });
      await loadUsers();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to create user');
    }
  };

  const updateRole = async (userId: string, role: RoleName) => {
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.users = dataset.users.map((user) => (user.id === userId ? { ...user, role } : user));
        saveDemoDataset(dataset);
        setRows(dataset.users as UserRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest(`/users/${userId}/role`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({ role }),
      });
      await loadUsers();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to update role');
    }
  };

  const toggleActive = async (user: UserRow) => {
    try {
      if (isDemoMode) {
        const dataset = getDemoDataset();
        dataset.users = dataset.users.map((item) =>
          item.id === user.id ? { ...item, isActive: !item.isActive } : item,
        );
        saveDemoDataset(dataset);
        setRows(dataset.users as UserRow[]);
        return;
      }

      const session = getSession();
      if (!session) return;

      await apiRequest(`/users/${user.id}`, session.accessToken, {
        method: 'PATCH',
        body: JSON.stringify({ isActive: !user.isActive }),
      });
      await loadUsers();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to update user status');
    }
  };

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-900 via-slate-800 to-indigo-900 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">Users & Roles Center</h2>
        <p className="mt-2 max-w-2xl text-sm text-slate-200">Manage accounts, switch roles, activate or suspend access, and create new app users from one place.</p>
        <div className="mt-4 flex flex-wrap gap-3 text-xs">
          <span className="rounded-full bg-white/15 px-3 py-1.5">{counts.total} users</span>
          <span className="rounded-full bg-emerald-400/20 px-3 py-1.5 text-emerald-100">{counts.active} active</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{counts.admins} admins</span>
          <span className="rounded-full bg-white/15 px-3 py-1.5">{counts.caretakers} caretakers</span>
        </div>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-y-auto pb-8 pr-1">
        <div className="space-y-8">
          {error ? <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div> : null}

          <div className="grid gap-6 xl:grid-cols-[380px_1fr]">
            <form onSubmit={createUser} className="tp-form-panel">
              <h3 className="text-lg font-semibold">Create User</h3>
              <input value={createForm.phoneNumber} onChange={(e) => setCreateForm({ ...createForm, phoneNumber: e.target.value })} className="tp-input" placeholder="Phone number" required />
              <input value={createForm.email} onChange={(e) => setCreateForm({ ...createForm, email: e.target.value })} className="tp-input" placeholder="Email (optional)" />
              <div className="grid gap-3 md:grid-cols-2">
                <input value={createForm.firstName} onChange={(e) => setCreateForm({ ...createForm, firstName: e.target.value })} className="tp-input" placeholder="First name" />
                <input value={createForm.lastName} onChange={(e) => setCreateForm({ ...createForm, lastName: e.target.value })} className="tp-input" placeholder="Last name" />
              </div>
              <input value={createForm.password} onChange={(e) => setCreateForm({ ...createForm, password: e.target.value })} className="tp-input" placeholder="Password (optional)" />
              <select value={createForm.role} onChange={(e) => setCreateForm({ ...createForm, role: e.target.value as RoleName })} className="tp-select">
                {roleOptions.map((role) => (
                  <option key={role} value={role}>{role}</option>
                ))}
              </select>
              <button type="submit" className="tp-primary-btn">Create User</button>
            </form>

            <section className="space-y-4 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
              <div className="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                <h3 className="text-lg font-semibold">Access Control</h3>
                <div className="inline-flex rounded-xl border border-gray-200 bg-gray-50 p-1">
                  {(['ALL', ...roleOptions] as const).map((role) => (
                    <button
                      key={role}
                      type="button"
                      onClick={() => setRoleFilter(role)}
                      className={`rounded-lg px-3 py-1.5 text-sm transition ${roleFilter === role ? 'bg-white text-slate-900 shadow-sm' : 'text-gray-600'}`}
                    >
                      {role}
                    </button>
                  ))}
                </div>
              </div>

              <div className="overflow-x-auto">
                <table className="min-w-full text-sm">
                  <thead className="text-left text-gray-500">
                    <tr className="border-b border-gray-100">
                      <th className="px-3 py-3">User</th>
                      <th className="px-3 py-3">Phone</th>
                      <th className="px-3 py-3">Role</th>
                      <th className="px-3 py-3">Status</th>
                      <th className="px-3 py-3">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {filteredRows.map((user) => (
                      <tr key={user.id} className="border-b border-gray-100 align-top">
                        <td className="px-3 py-3">
                          <div className="font-medium text-gray-900">{[user.firstName, user.lastName].filter(Boolean).join(' ') || 'Unnamed User'}</div>
                          <div className="text-xs text-gray-500">{user.email ?? 'No email'}</div>
                        </td>
                        <td className="px-3 py-3 text-gray-700">{user.phoneNumber}</td>
                        <td className="px-3 py-3">
                          <select
                            value={user.role}
                            onChange={(e) => void updateRole(user.id, e.target.value as RoleName)}
                            className="tp-select !w-auto !px-2 !py-1.5"
                          >
                            {roleOptions.map((role) => (
                              <option key={role} value={role}>{role}</option>
                            ))}
                          </select>
                        </td>
                        <td className="px-3 py-3">
                          <span className={`rounded-full px-2.5 py-1 text-xs font-medium ${user.isActive ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                            {user.isActive ? 'Active' : 'Inactive'}
                          </span>
                        </td>
                        <td className="px-3 py-3">
                          <button
                            type="button"
                            onClick={() => void toggleActive(user)}
                            className="tp-secondary-btn !px-3 !py-1.5"
                          >
                            {user.isActive ? 'Deactivate' : 'Activate'}
                          </button>
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
                {loading ? <p className="px-3 py-4 text-sm text-gray-500">Loading users...</p> : null}
              </div>
            </section>
          </div>
        </div>
      </section>
    </main>
  );
}
