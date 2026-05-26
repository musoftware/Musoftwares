<?php

$file = 'd:\Projects\1AOrganized\PhpProject\MusoftwareBusiness\Musoftwares\resources\js\Pages\Admin\Users\Show.jsx';
$content = file_get_contents($file);

// Replace Activity Overview Grid
$oldGrid = '<div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.invoices_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Invoices</div>
                    <div className="text-xs text-green-600 font-medium mt-1">{stats.invoices_paid || 0} Paid</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.tickets_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Tickets</div>
                    <div className="text-xs text-amber-600 font-medium mt-1">{stats.tickets_open || 0} Open</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.kyc_docs_count || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">KYC Docs</div>
                    <div className="text-xs text-slate-400 font-medium mt-1">Uploaded</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{client.referrals || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Referrals</div>
                    <div className="text-xs text-slate-400 font-medium mt-1">Total</div>
                </div>
            </div>';

$newGrid = '<div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.invoices_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Invoices</div>
                    <div className="text-xs text-green-600 font-medium mt-1">{stats.invoices_paid || 0} Paid</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.tickets_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Tickets</div>
                    <div className="text-xs text-amber-600 font-medium mt-1">{stats.tickets_open || 0} Open</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.orders_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Orders</div>
                    <div className="text-xs text-slate-400 font-medium mt-1">Total</div>
                </div>
                <div className="bg-white p-4 rounded-[12px] shadow-sm border border-slate-200 text-center">
                    <div className="text-3xl font-bold font-jetbrains text-slate-900 mb-1">{stats.services_total || 0}</div>
                    <div className="text-xs font-bold uppercase tracking-wider text-slate-500">Services</div>
                    <div className="text-xs text-green-600 font-medium mt-1">{stats.services_approved || 0} Approved</div>
                </div>
            </div>';

$content = str_replace($oldGrid, $newGrid, $content);

// Financial Summary Replace
$oldFin = '                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Pending Comm.</span>
                                <span className="font-bold text-amber-600 font-jetbrains">{formatCurrency(client.pending_commission || 0, client.currency)}</span>
                            </div>
                        </div>';

$newFin = '                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Remaining</span>
                                <span className="font-bold text-green-600 font-jetbrains">{formatCurrency((client.available_balance || 0) - (stats.invoices_unpaid_sum || 0), client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Pending Comm.</span>
                                <span className="font-bold text-amber-600 font-jetbrains">{formatCurrency(client.pending_commission || 0, client.currency)}</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Work Time</span>
                                <span className="font-bold text-slate-900">0h 0m</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Invoiced Days</span>
                                <span className="font-bold text-slate-900">0 days</span>
                            </div>
                            <div className="flex justify-between items-center pb-2 border-b border-slate-100">
                                <span className="text-slate-500 text-sm">Reward Points</span>
                                <span className="px-2 py-0.5 bg-amber-100 text-amber-800 rounded font-bold text-xs">0</span>
                            </div>
                        </div>';

$content = str_replace($oldFin, $newFin, $content);

// Mail Sequence Component (Inject before Subscription)
$subSearch = '{/* Subscription / Memberships */}';
$mailSeq = '{/* Mail Sequence */}
                    <div className="bg-white p-6 rounded-[12px] shadow-sm border border-slate-200 mb-6">
                        <h2 className="text-lg font-bold font-sora text-slate-900 mb-4 border-b pb-2 flex items-center gap-2">
                            <Mail size={18} className="text-slate-400" /> Mail Sequence
                        </h2>
                        {client.active_mail_sequence ? (
                            <div>
                                <div className="p-3 bg-green-50 border border-green-200 rounded-[8px] flex items-center gap-3 mb-4">
                                    <div className="w-8 h-8 rounded-full bg-green-100 flex items-center justify-center text-green-600">
                                        <MessageCircle size={16} />
                                    </div>
                                    <div>
                                        <div className="font-bold text-green-900">{client.active_mail_sequence.name || "Active Sequence"}</div>
                                        <div className="text-xs text-green-700">Current Step: {client.active_mail_sequence.step || 1}</div>
                                    </div>
                                </div>
                                <Button variant="outline" className="w-full text-red-600 hover:text-red-700 hover:bg-red-50 border-red-200" onClick={() => alert("Opt out functionality not implemented.")}>
                                    Opt-Out User
                                </Button>
                            </div>
                        ) : (
                            <div>
                                <div className="p-3 bg-slate-50 border border-slate-200 rounded-[8px] flex items-center gap-3 mb-4 text-slate-500">
                                    <div className="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center">
                                        <Mail size={16} />
                                    </div>
                                    <div className="text-sm font-medium">No active mail sequence</div>
                                </div>
                                <Button className="w-full bg-slate-900 text-white hover:bg-slate-800" onClick={() => alert("Enroll functionality not implemented.")}>
                                    Opt-In Default Sequence
                                </Button>
                            </div>
                        )}
                    </div>

                    ';
$content = str_replace($subSearch, $mailSeq . $subSearch, $content);

// Circular Subscription Ring
$oldSub = '                            <div>
                                <div className="text-slate-900 font-bold">{client.subscription_plan || "Custom Plan"}</div>
                                <div className="text-sm text-slate-500">Expires on: {new Date(client.subscription_date).toLocaleDateString()}</div>
                            </div>
                            {new Date(client.subscription_date) > new Date() ? (
                                <span className="px-3 py-1 bg-green-50 text-green-700 rounded-full text-xs font-bold border border-green-200">Active</span>
                            ) : (
                                <span className="px-3 py-1 bg-red-50 text-red-700 rounded-full text-xs font-bold border border-red-200">Expired</span>
                            )}';

$newSub = '                            {new Date(client.subscription_date) > new Date() ? (() => {
                                const daysRemaining = Math.max(0, Math.ceil((new Date(client.subscription_date) - new Date()) / (1000 * 60 * 60 * 24)));
                                const percentage = Math.min(100, Math.max(0, (daysRemaining / 30) * 100)); // Assuming 30 days plan for display
                                const dashArray = 2 * Math.PI * 52;
                                const dashOffset = dashArray - ((percentage / 100) * dashArray);
                                
                                return (
                                    <div className="w-full text-center">
                                        <div className="relative w-[120px] h-[120px] mx-auto mb-4">
                                            <svg viewBox="0 0 120 120" className="w-full h-full transform -rotate-90">
                                                <circle cx="60" cy="60" r="52" fill="none" stroke="#f1f5f9" strokeWidth="8" />
                                                <circle 
                                                    cx="60" cy="60" r="52" fill="none" stroke="#10b981" strokeWidth="8"
                                                    strokeDasharray={dashArray} strokeDashoffset={dashOffset} strokeLinecap="round"
                                                    className="transition-all duration-1000 ease-out"
                                                />
                                            </svg>
                                            <div className="absolute inset-0 flex flex-col items-center justify-center">
                                                <span className="text-2xl font-bold text-slate-900 font-jetbrains">{daysRemaining}</span>
                                                <span className="text-xs text-slate-500 uppercase font-bold">Days</span>
                                            </div>
                                        </div>
                                        <div className="text-slate-900 font-bold mb-1">{client.subscription_plan || "Custom Plan"}</div>
                                        <div className="text-sm text-slate-500">Expires: {new Date(client.subscription_date).toLocaleDateString()}</div>
                                    </div>
                                );
                            })() : (
                                <div className="w-full text-center py-6">
                                    <div className="w-16 h-16 bg-red-50 rounded-full flex items-center justify-center mx-auto mb-4 text-red-500">
                                        <Trash2 size={24} />
                                    </div>
                                    <h5 className="text-red-600 font-bold text-lg mb-1">Expired</h5>
                                    <p className="text-sm text-slate-500">Subscription has ended</p>
                                </div>
                            )}';
$content = str_replace($oldSub, $newSub, $content);

// Personal info fields
$oldInfo = '                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Phone</span><span className="font-medium text-slate-900 break-words">{client.phone || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">WhatsApp</span><span className="font-medium text-slate-900 break-words">{client.whatsapp_number || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                            </div>';

$newInfo = '                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Currency</span><span className="font-medium text-slate-900 break-words">{client.currency || <span className="text-slate-400 italic">Default</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Hour Rate (USD)</span><span className="font-medium text-slate-900 break-words">{client.hour_rate || "0.00"}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Phone</span><span className="font-medium text-slate-900 break-words">{client.phone || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">WhatsApp</span><span className="font-medium text-slate-900 break-words">{client.whatsapp_number || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                            </div>';
$content = str_replace($oldInfo, $newInfo, $content);

$oldInfo2 = '                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Job</span><span className="font-medium text-slate-900 break-words">{client.job || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Joined</span><span className="font-medium text-slate-900">{client.created_at ? new Date(client.created_at).toLocaleDateString() : "N/A"}</span></div>
                            </div>';

$newInfo2 = '                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Job</span><span className="font-medium text-slate-900 break-words">{client.job || <span className="text-slate-400 italic">Not provided</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Joined</span><span className="font-medium text-slate-900">{client.created_at ? new Date(client.created_at).toLocaleDateString() : "N/A"}</span></div>
                            </div>
                            <div className="grid grid-cols-2 gap-2">
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">Start Date</span><span className="font-medium text-slate-900">{client.date_start ? new Date(client.date_start).toLocaleDateString() : <span className="text-slate-400 italic">Not provided</span>}</span></div>
                                <div><span className="text-slate-500 block text-xs uppercase tracking-wider font-bold mb-1">End Date</span><span className="font-medium text-slate-900">{client.date_end ? new Date(client.date_end).toLocaleDateString() : <span className="text-slate-400 italic">Not provided</span>}</span></div>
                            </div>';
$content = str_replace($oldInfo2, $newInfo2, $content);

file_put_contents($file, $content);
echo "Success";
