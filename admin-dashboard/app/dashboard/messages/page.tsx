"use client";

import { ChangeEvent, FormEvent, useEffect, useMemo, useRef, useState } from 'react';
import { useSearchParams } from 'next/navigation';
import { API_BASE_URL, apiRequest, uploadFile } from '../../../lib/api';
import { getSession } from '../../../lib/auth';
import { getDemoDataset, saveDemoDataset } from '../../../lib/demo-tenant-ops';

type ConversationSummary = {
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

type SupportMessage = {
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

type ConversationPayload = {
  conversation: ConversationSummary;
  messages: SupportMessage[];
};

type UploadResponse = {
  fileName: string;
  attachmentName: string;
  attachmentUri: string;
  size: number;
  mimeType: string;
};

export default function MessagesPage() {
  const searchParams = useSearchParams();
  const isDemoMode = searchParams.get('mode') === 'demo';
  const requestedTenantId = searchParams.get('tenantId') ?? '';
  const requestedTenantName = searchParams.get('tenantName') ?? '';

  const [conversations, setConversations] = useState<ConversationSummary[]>([]);
  const [selectedConversationId, setSelectedConversationId] = useState<string | null>(null);
  const [selectedConversation, setSelectedConversation] = useState<ConversationSummary | null>(null);
  const [messages, setMessages] = useState<SupportMessage[]>([]);
  const [messageText, setMessageText] = useState('');
  const [selectedFile, setSelectedFile] = useState<File | null>(null);
  const [loadingList, setLoadingList] = useState(false);
  const [loadingThread, setLoadingThread] = useState(false);
  const [sending, setSending] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const pollInFlightRef = useRef(false);

  const selectedRequestedTenant = useMemo(
    () => conversations.find((item) => item.tenantUserId === requestedTenantId) ?? null,
    [conversations, requestedTenantId],
  );

  const loadConversations = async (silent = false) => {
    try {
      if (!silent) {
        setLoadingList(true);
        setError(null);
      }

      let data: ConversationSummary[] = [];

      if (isDemoMode) {
        const dataset = getDemoDataset();
        data = dataset.conversations as ConversationSummary[];
      } else {
        const session = getSession();
        if (!session) return;
        data = await apiRequest<ConversationSummary[]>('/support/conversations', session.accessToken);
      }

      setConversations((current) => {
        const same =
          current.length === data.length &&
          current.every((item, index) => {
            const next = data[index];
            return next && item.id === next.id && item.lastMessageAt === next.lastMessageAt && item.lastMessage === next.lastMessage;
          });
        return same ? current : data;
      });

      const preferred =
        (requestedTenantId && data.find((item) => item.tenantUserId === requestedTenantId)?.id) ||
        data[0]?.id ||
        null;

      setSelectedConversationId((current) => {
        if (current && data.some((item) => item.id === current)) return current;
        return preferred;
      });
    } catch (requestError) {
      if (!silent) {
        setError(requestError instanceof Error ? requestError.message : 'Failed to load conversations');
      }
    } finally {
      if (!silent) {
        setLoadingList(false);
      }
    }
  };

  const loadMessages = async (conversationId: string, silent = false) => {
    try {
      if (!silent) {
        setLoadingThread(true);
        setError(null);
      }

      let payload: ConversationPayload;

      if (isDemoMode) {
        const dataset = getDemoDataset();
        const conversation = (dataset.conversations as ConversationSummary[]).find((item) => item.id === conversationId);
        if (!conversation) {
          setSelectedConversation(null);
          setMessages([]);
          return;
        }
        payload = {
          conversation,
          messages: (dataset.messagesByConversation[conversationId] ?? []) as SupportMessage[],
        };
      } else {
        const session = getSession();
        if (!session) return;
        payload = await apiRequest<ConversationPayload>(`/support/conversations/${conversationId}/messages`, session.accessToken);
      }

      setSelectedConversation(payload.conversation);
      setMessages((current) => {
        const same =
          current.length === payload.messages.length &&
          current.every((item, index) => {
            const next = payload.messages[index];
            return next && item.id === next.id && item.timestamp === next.timestamp && item.message === next.message && item.status === next.status;
          });
        return same ? current : payload.messages;
      });
    } catch (requestError) {
      if (!silent) {
        setError(requestError instanceof Error ? requestError.message : 'Failed to load messages');
      }
    } finally {
      if (!silent) {
        setLoadingThread(false);
      }
    }
  };

  useEffect(() => {
    void loadConversations();
  }, [isDemoMode]);

  useEffect(() => {
    if (selectedConversationId) {
      void loadMessages(selectedConversationId);
    } else {
      setSelectedConversation(null);
      setMessages([]);
    }
  }, [selectedConversationId]);

  useEffect(() => {
    if (isDemoMode) {
      return undefined;
    }

    const interval = window.setInterval(async () => {
      if (pollInFlightRef.current) return;
      pollInFlightRef.current = true;

      try {
        await loadConversations(true);
        if (selectedConversationId) {
          await loadMessages(selectedConversationId, true);
        }
      } finally {
        pollInFlightRef.current = false;
      }
    }, 3000);

    return () => window.clearInterval(interval);
  }, [isDemoMode, selectedConversationId]);

  const onFileChange = (event: ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0] ?? null;
    setSelectedFile(file);
  };

  const sendMessage = async (event: FormEvent) => {
    event.preventDefault();

    const trimmed = messageText.trim();
    if (!trimmed && !selectedFile) {
      setError('Type a message or choose a file before sending.');
      return;
    }

    try {
      setSending(true);
      setError(null);

      if (isDemoMode) {
        const dataset = getDemoDataset();
        const existingConversation = selectedConversationId
          ? (dataset.conversations as ConversationSummary[]).find((item) => item.id === selectedConversationId) ?? null
          : null;

        let conversation = existingConversation;
        if (!conversation) {
          if (!requestedTenantId) {
            setError('Choose a tenant conversation first.');
            return;
          }

          const tenant = dataset.users.find((item) => item.id === requestedTenantId);
          conversation = {
            id: `demo-convo-${Date.now()}`,
            tenantUserId: requestedTenantId,
            tenantName: requestedTenantName || (tenant ? `${tenant.firstName} ${tenant.lastName}` : 'Tenant'),
            phoneNumber: tenant?.phoneNumber ?? 'N/A',
            email: tenant?.email ?? null,
            isTenantActive: tenant?.isActive ?? true,
            topic: 'General',
            subject: requestedTenantName ? `${requestedTenantName} chat` : 'Tenant chat',
            isOpen: true,
            lastMessage: '',
            lastMessageAt: new Date().toISOString(),
            lastSender: 'staff',
          };
          dataset.conversations.unshift(conversation);
          setSelectedConversationId(conversation.id);
        }

        const newMessage: SupportMessage = {
          id: `demo-msg-${Date.now()}`,
          senderId: 'demo-manager',
          topic: conversation.topic,
          message: trimmed || `Shared attachment: ${selectedFile?.name ?? 'file'}`,
          attachmentUri: selectedFile ? '#' : null,
          attachmentName: selectedFile?.name ?? null,
          isFromTenant: false,
          status: 'SENT',
          timestamp: Date.now(),
        };

        const updatedMessages = [
          ...(((dataset.messagesByConversation[conversation.id] ?? []) as SupportMessage[])),
          newMessage,
        ];

        dataset.messagesByConversation[conversation.id] = updatedMessages;
        dataset.conversations = (dataset.conversations as ConversationSummary[])
          .map((item) =>
            item.id === conversation!.id
              ? {
                  ...item,
                  lastMessage: newMessage.message,
                  lastMessageAt: new Date(newMessage.timestamp).toISOString(),
                  lastSender: 'staff' as const,
                  isOpen: true,
                }
              : item,
          )
          .sort((a, b) => new Date(b.lastMessageAt).getTime() - new Date(a.lastMessageAt).getTime());

        saveDemoDataset(dataset);
        const refreshedConversation = (dataset.conversations as ConversationSummary[]).find((item) => item.id === conversation.id) ?? conversation;
        setConversations(dataset.conversations as ConversationSummary[]);
        setSelectedConversation(refreshedConversation);
        setMessages(updatedMessages);
        setMessageText('');
        setSelectedFile(null);
        return;
      }

      const session = getSession();
      if (!session) return;

      let uploaded: UploadResponse | null = null;
      if (selectedFile) {
        uploaded = await uploadFile<UploadResponse>('/support/upload', session.accessToken, selectedFile);
      }

      let payload: ConversationPayload;
      if (selectedConversationId) {
        payload = await apiRequest<ConversationPayload>(
          `/support/conversations/${selectedConversationId}/messages`,
          session.accessToken,
          {
            method: 'POST',
            body: JSON.stringify({
              text: trimmed || undefined,
              topic: selectedConversation?.topic ?? 'General',
              attachmentUri: uploaded?.attachmentUri,
              attachmentName: uploaded?.attachmentName,
            }),
          },
        );
      } else if (requestedTenantId) {
        payload = await apiRequest<ConversationPayload>('/support/conversations', session.accessToken, {
          method: 'POST',
          body: JSON.stringify({
            tenantUserId: requestedTenantId,
            text: trimmed || undefined,
            topic: 'General',
            subject: requestedTenantName ? `${requestedTenantName} chat` : 'Tenant chat',
            attachmentUri: uploaded?.attachmentUri,
            attachmentName: uploaded?.attachmentName,
          }),
        });
        setSelectedConversationId(payload.conversation.id);
      } else {
        setError('Choose a tenant conversation first.');
        return;
      }

      setSelectedConversation(payload.conversation);
      setMessages(payload.messages);
      setMessageText('');
      setSelectedFile(null);
      await loadConversations();
    } catch (requestError) {
      setError(requestError instanceof Error ? requestError.message : 'Failed to send message');
    } finally {
      setSending(false);
    }
  };

  return (
    <main className="mx-auto flex h-[calc(100dvh-6.5rem)] w-full max-w-7xl flex-col text-gray-900 lg:h-[calc(100dvh-4rem)]">
      <section className="shrink-0 rounded-2xl border border-gray-200 bg-gradient-to-r from-slate-900 via-indigo-900 to-cyan-700 px-6 py-8 text-white shadow-xl">
        <h2 className="text-3xl font-bold tracking-tight">One-on-One Tenant Messaging</h2>
        <p className="mt-2 max-w-2xl text-sm text-slate-200">
          Reply to tenant chats in real time and keep communication organized by conversation.
        </p>
      </section>

      <section className="mt-6 min-h-0 flex-1 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
        {error ? (
          <div className="border-b border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{error}</div>
        ) : null}

        <div className="grid h-full min-h-0 lg:grid-cols-[320px_1fr]">
          <aside className="min-h-0 border-r border-gray-200 bg-gray-50/80">
            <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
              <div>
                <div className="text-sm font-semibold text-gray-900">Conversations</div>
                <div className="text-xs text-gray-500">{conversations.length} active threads</div>
              </div>
              <button
                type="button"
                onClick={() => void loadConversations()}
                className="rounded-lg border border-gray-300 px-2.5 py-1 text-xs text-gray-700 hover:bg-gray-100"
              >
                Refresh
              </button>
            </div>

            <div className="max-h-full overflow-y-auto p-2">
              {loadingList && conversations.length === 0 ? <p className="p-3 text-sm text-gray-500">Loading conversations...</p> : null}
              {!loadingList && conversations.length === 0 ? (
                <p className="p-3 text-sm text-gray-500">No tenant conversations yet.</p>
              ) : null}

              <div className="space-y-2">
                {conversations.map((conversation) => {
                  const active = conversation.id === selectedConversationId;
                  return (
                    <button
                      key={conversation.id}
                      type="button"
                      onClick={() => setSelectedConversationId(conversation.id)}
                      className={`w-full rounded-xl border px-3 py-3 text-left transition ${
                        active
                          ? 'border-slate-900 bg-slate-900 text-white'
                          : 'border-gray-200 bg-white hover:bg-gray-100'
                      }`}
                    >
                      <div className="flex items-center justify-between gap-2">
                        <div className="font-medium">{conversation.tenantName}</div>
                        <span className={`rounded-full px-2 py-0.5 text-[10px] ${active ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600'}`}>
                          {conversation.topic}
                        </span>
                      </div>
                      <div className={`mt-1 text-xs ${active ? 'text-slate-200' : 'text-gray-500'}`}>
                        {conversation.phoneNumber}
                      </div>
                      <div className={`mt-2 line-clamp-2 text-xs ${active ? 'text-slate-100' : 'text-gray-700'}`}>
                        {conversation.lastMessage}
                      </div>
                    </button>
                  );
                })}
              </div>
            </div>
          </aside>

          <section className="flex min-h-0 flex-col">
            <div className="border-b border-gray-200 px-4 py-3">
              <div className="text-sm font-semibold text-gray-900">
                {selectedConversation?.tenantName || requestedTenantName || 'Select a conversation'}
              </div>
              <div className="text-xs text-gray-500">
                {selectedConversation?.phoneNumber || (requestedTenantName ? 'Start a new chat from this tenant profile.' : 'Choose a tenant thread to view messages.')}
              </div>
            </div>

            <div className="min-h-0 flex-1 overflow-y-auto bg-gray-50 px-4 py-4">
              {loadingThread && messages.length === 0 ? <p className="text-sm text-gray-500">Loading messages...</p> : null}

              {!loadingThread && messages.length === 0 ? (
                <div className="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-sm text-gray-500">
                  {requestedTenantId
                    ? `Start a new conversation with ${requestedTenantName || 'this tenant'} below.`
                    : 'No messages in this thread yet.'}
                </div>
              ) : null}

              <div className="space-y-3">
                {messages.map((message) => (
                  <div
                    key={message.id}
                    className={`max-w-[78%] rounded-2xl px-4 py-3 text-sm shadow-sm ${
                      message.isFromTenant
                        ? 'mr-auto bg-white text-gray-800'
                        : 'ml-auto bg-slate-900 text-white'
                    }`}
                  >
                    <div className="mb-1 text-[11px] opacity-70">
                      {message.isFromTenant ? 'Tenant' : 'Management'} • {new Date(message.timestamp).toLocaleString()}
                    </div>
                    <div>{message.message}</div>
                    {message.attachmentName ? (
                      <div className="mt-2 text-[11px] opacity-90">
                        Attachment:{' '}
                        <a
                          href={message.attachmentUri?.startsWith('http') ? message.attachmentUri : `${API_BASE_URL.replace('/api', '')}${message.attachmentUri ?? ''}`}
                          target="_blank"
                          rel="noreferrer"
                          className="underline"
                        >
                          {message.attachmentName}
                        </a>
                      </div>
                    ) : null}
                  </div>
                ))}
              </div>
            </div>

            <form onSubmit={sendMessage} className="border-t border-gray-200 bg-white p-4">
              <div className="mb-2 text-xs text-gray-500">
                {selectedConversation
                  ? `Replying to ${selectedConversation.tenantName}`
                  : requestedTenantName
                    ? `Starting a chat with ${requestedTenantName}`
                    : 'Select a tenant conversation to reply.'}
              </div>

              {selectedFile ? (
                <div className="mb-3 inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs text-gray-700">
                  <span>📎</span>
                  <span>{selectedFile.name}</span>
                </div>
              ) : null}

              <div className="flex items-end gap-3">
                <textarea
                  value={messageText}
                  onChange={(event) => setMessageText(event.target.value)}
                  className="min-h-[56px] flex-1 rounded-xl border border-gray-300 px-3 py-2 text-sm outline-none focus:border-slate-500"
                  placeholder="Type your message to the tenant..."
                />
                <button
                  type="submit"
                  disabled={sending || (!selectedConversationId && !requestedTenantId)}
                  className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-900 text-sm font-medium text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-50"
                  title="Send message"
                >
                  {sending ? '…' : '➤'}
                </button>
                <label
                  className="flex h-10 w-10 cursor-pointer items-center justify-center rounded-full border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-100"
                  title="Attach file"
                >
                  📎
                  <input type="file" className="hidden" onChange={onFileChange} />
                </label>
              </div>
            </form>
          </section>
        </div>
      </section>
    </main>
  );
}
