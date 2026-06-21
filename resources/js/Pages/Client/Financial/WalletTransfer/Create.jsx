import React, { useState, useEffect, useCallback } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import { Wallet, Search, Check, AlertCircle, ArrowRight, ArrowLeft, Send, Sparkles, CheckCircle, Info } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/Components/ui/card';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Button } from '@/Components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';

// Simple debounce helper
function useDebounce(value, delay) {
    const [debouncedValue, setDebouncedValue] = useState(value);

    useEffect(() => {
        const handler = setTimeout(() => {
            setDebouncedValue(value);
        }, delay);

        return () => {
            clearTimeout(handler);
        };
    }, [value, delay]);

    return debouncedValue;
}

export default function Create({ user, wallet }) {
    const [searchQuery, setSearchQuery] = useState('');
    const [searchResults, setSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);
    const [selectedRecipient, setSelectedRecipient] = useState(null);
    const [step, setStep] = useState(1);

    // Live preview metrics
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewData, setPreviewData] = useState(null);
    const [previewError, setPreviewError] = useState(null);

    const debouncedSearch = useDebounce(searchQuery, 400);

    const { data, setData, post, processing, errors, reset } = useForm({
        receiver_email: '',
        amount: '',
        reason: '',
        confirm_transfer: false,
    });

    // Handle autocomplete user search
    useEffect(() => {
        if (debouncedSearch.length < 5) {
            setSearchResults([]);
            return;
        }

        setSearching(true);
        fetch(route('financial.transfer.search-users') + `?q=${encodeURIComponent(debouncedSearch)}`)
            .then(res => res.json())
            .then(data => {
                setSearchResults(data.users || []);
                setSearching(false);
            })
            .catch(err => {
                console.error(err);
                setSearching(false);
            });
    }, [debouncedSearch]);

    // Handle recipient selection
    const selectRecipient = (recipient) => {
        setSelectedRecipient(recipient);
        setData('receiver_email', recipient.email);
        setSearchQuery('');
        setSearchResults([]);
        setStep(2);
    };

    const clearRecipient = () => {
        setSelectedRecipient(null);
        setData('receiver_email', '');
        setPreviewData(null);
        setStep(1);
    };

    // Calculate live fee & exchange preview as user enters amount
    const debouncedAmount = useDebounce(data.amount, 500);

    useEffect(() => {
        if (!selectedRecipient || !debouncedAmount || parseFloat(debouncedAmount) <= 0) {
            setPreviewData(null);
            setPreviewError(null);
            return;
        }

        setPreviewLoading(true);
        setPreviewError(null);

        fetch(route('financial.transfer.calculate-fee') + `?amount=${debouncedAmount}&receiver_email=${encodeURIComponent(selectedRecipient.email)}`)
            .then(async (res) => {
                const responseData = await res.json();
                if (!res.ok) {
                    throw new Error(responseData.message || 'Failed to estimate transaction details.');
                }
                return responseData;
            })
            .then(res => {
                if (res.success) {
                    setPreviewData(res.preview);
                } else {
                    setPreviewError(res.message);
                }
                setPreviewLoading(false);
            })
            .catch(err => {
                setPreviewError(err.message);
                setPreviewLoading(false);
            });
    }, [debouncedAmount, selectedRecipient]);

    // Handle Form Submit
    const handleSubmit = (e) => {
        e.preventDefault();
        post(route('financial.transfer.store'), {
            onError: (err) => {
                console.error(err);
            }
        });
    };

    return (
        <AuthenticatedLayout header="Send Wallet Funds">
            <Head title={__('general.send_transfer')} />

            <div className="max-w-[700px] mx-auto px-4 py-8 space-y-8">
                
                {/* Header with back link */}
                <div className="flex items-center justify-between">
                    <div className="space-y-1">
                        <h1 className="text-2xl font-semibold tracking-tight">{__('general.peer_to_peer_transfer')}</h1>
                        <p className="text-sm text-muted-foreground">{__('general.send_funds_instantly_to_another_platform_user_s_wallet')}</p>
                    </div>
                    <Button variant="outline" size="sm" asChild className="shadow-none">
                        <Link href={route('financial.transfer.history')}>History</Link>
                    </Button>
                </div>

                {/* Form & Card Wrapper */}
                <Card className="shadow-none border-primary/10">
                    <CardHeader className="bg-muted/10 border-b border-primary/5 pb-4">
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-3">
                                <div className="p-2 rounded-lg bg-primary/10 text-primary">
                                    <Wallet className="w-5 h-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-sm font-medium">{__('general.your_platform_balance')}</CardTitle>
                                    <div className="text-lg font-bold text-foreground">
                                        {Number(wallet.balance).toFixed(2)} <span className="text-xs font-normal text-muted-foreground">{wallet.currency}</span>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Wizard Progress Steps Indicator */}
                            <div className="flex items-center gap-1.5 text-xs font-medium text-muted-foreground">
                                <span className={step === 1 ? "text-primary" : "text-muted-foreground/60"}>1. Recipient</span>
                                <ArrowRight className="w-3 h-3 text-muted-foreground/40" />
                                <span className={step === 2 ? "text-primary" : "text-muted-foreground/60"}>2. Amount</span>
                                <ArrowRight className="w-3 h-3 text-muted-foreground/40" />
                                <span className={step === 3 ? "text-primary" : "text-muted-foreground/60"}>3. Confirm</span>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent className="pt-6">
                        
                        <form onSubmit={handleSubmit} className="space-y-6">
                            
                            {/* STEP 1: Select Recipient */}
                            {step === 1 && (
                                <div className="space-y-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="receiver_search">{__('general.search_recipient')}</Label>
                                        <div className="relative">
                                            <Search className="absolute start-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                            <Input
                                                id="receiver_search"
                                                type="text"
                                                className="ps-9 shadow-none"
                                                placeholder={__('general.enter_recipient_s_name_or_email')}
                                                value={searchQuery}
                                                onChange={(e) => setSearchQuery(e.target.value)}
                                            />
                                        </div>
                                    </div>

                                    {/* Auto-complete Search Results dropdown panel */}
                                    {searching ? (
                                        <div className="p-4 border rounded-md text-center text-sm text-muted-foreground">{__('general.searching_for_matching_accounts')}</div>
                                    ) : searchResults.length > 0 ? (
                                        <div className="border rounded-lg divide-y bg-background shadow-sm max-h-[300px] overflow-y-auto">
                                            {searchResults.map((userMatch) => (
                                                <button
                                                    key={userMatch.id}
                                                    type="button"
                                                    className="w-full px-4 py-3 flex items-center justify-between text-start hover:bg-muted/30 transition-colors"
                                                    onClick={() => selectRecipient(userMatch)}
                                                >
                                                    <div className="flex flex-col">
                                                        <span className="font-medium text-sm">{userMatch.name}</span>
                                                        <span className="text-xs text-muted-foreground">{userMatch.email}</span>
                                                    </div>
                                                    <ArrowRight className="w-4 h-4 text-muted-foreground" />
                                                </button>
                                            ))}
                                        </div>
                                    ) : searchQuery.length >= 5 ? (
                                        <div className="p-4 border rounded-md text-center text-sm text-muted-foreground">
                                            No platform accounts found matching "{searchQuery}".
                                        </div>
                                    ) : (
                                        <div className="p-4 border border-dashed rounded-lg text-center text-xs text-muted-foreground bg-muted/5 flex flex-col items-center gap-2">
                                            <Info className="w-4 h-4 text-muted-foreground/60" />{__('general.start_typing_a_recipient_s_email_address_or_full_name_to_lookup')}</div>
                                    )}
                                    
                                    {errors.receiver_email && (
                                        <p className="text-sm text-rose-600 font-medium">{errors.receiver_email}</p>
                                    )}
                                </div>
                            )}

                            {/* STEP 2: Input Amount & Preview Fee */}
                            {step === 2 && selectedRecipient && (
                                <div className="space-y-6">
                                    
                                    {/* Selected Recipient Card */}
                                    <div className="p-4 border rounded-lg bg-muted/5 flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="w-10 h-10 rounded-full bg-primary/10 text-primary flex items-center justify-center font-semibold text-sm">
                                                {selectedRecipient.name.charAt(0).toUpperCase()}
                                            </div>
                                            <div>
                                                <span className="text-xs font-semibold text-primary uppercase tracking-wider">{__('general.recipient_selected')}</span>
                                                <h4 className="font-semibold text-sm text-foreground">{selectedRecipient.name}</h4>
                                                <p className="text-xs text-muted-foreground">{selectedRecipient.email}</p>
                                            </div>
                                        </div>
                                        <Button type="button" variant="ghost" size="sm" className="text-xs text-rose-600 hover:text-rose-700" onClick={clearRecipient}>
                                            Change
                                        </Button>
                                    </div>

                                    {/* Amount Input */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="amount">Transfer Amount ({wallet.currency})</Label>
                                            <div className="relative">
                                                <Input
                                                    id="amount"
                                                    type="number"
                                                    step="0.01"
                                                    className="shadow-none pe-12"
                                                    placeholder="0.00"
                                                    value={data.amount}
                                                    onChange={(e) => setData('amount', e.target.value)}
                                                />
                                                <div className="absolute end-3 top-2 text-sm font-semibold text-muted-foreground">
                                                    {wallet.currency}
                                                </div>
                                            </div>
                                            {errors.amount && (
                                                <p className="text-sm text-rose-600 font-medium">{errors.amount}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="reason">Memo / Reason (Optional)</Label>
                                            <Input
                                                id="reason"
                                                type="text"
                                                className="shadow-none"
                                                placeholder={__('general.e_g_project_bonus_freelance_help')}
                                                value={data.reason}
                                                onChange={(e) => setData('reason', e.target.value)}
                                            />
                                            {errors.reason && (
                                                <p className="text-sm text-rose-600 font-medium">{errors.reason}</p>
                                            )}
                                        </div>
                                    </div>

                                    {/* Live Preview Panel */}
                                    {previewLoading ? (
                                        <div className="p-6 border rounded-lg text-center text-sm text-muted-foreground bg-muted/5 animate-pulse">{__('general.calculating_fees_and_exchange_conversions')}</div>
                                    ) : previewError ? (
                                        <Alert variant="destructive" className="shadow-none">
                                            <AlertCircle className="h-4 w-4" />
                                            <AlertTitle>{__('general.validation_blocked')}</AlertTitle>
                                            <AlertDescription>{previewError}</AlertDescription>
                                        </Alert>
                                    ) : previewData ? (
                                        <div className="border rounded-lg divide-y bg-muted/5">
                                            <div className="p-4 flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">{__('general.principal_transfer_amount')}</span>
                                                <span className="font-semibold text-foreground">
                                                    {Number(previewData.amount).toFixed(2)} {previewData.currency}
                                                </span>
                                            </div>

                                            <div className="p-4 flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground flex items-center gap-1.5">{__('general.transfer_fee')}<span className="text-xs text-muted-foreground/60">(1% capped)</span>
                                                </span>
                                                <span className="font-semibold text-foreground">
                                                    +{Number(previewData.fee).toFixed(2)} {previewData.currency}
                                                </span>
                                            </div>

                                            {previewData.requires_conversion && (
                                                <div className="p-4 bg-primary/5 space-y-2">
                                                    <div className="flex items-center justify-between text-sm">
                                                        <span className="text-primary font-medium flex items-center gap-1">
                                                            <Sparkles className="w-3.5 h-3.5" />{__('general.exchange_rate_applied')}</span>
                                                        <span className="font-semibold text-primary">
                                                            1 {previewData.currency} = {Number(previewData.exchange_rate).toFixed(4)} {previewData.converted_currency}
                                                        </span>
                                                    </div>
                                                    <p className="text-xs text-muted-foreground">{__('general.includes_a_standard_1_5_security_exchange_margin_to_protect_platform_liquidity')}</p>
                                                </div>
                                            )}

                                            <div className="p-4 flex items-center justify-between text-sm bg-primary/10">
                                                <span className="font-bold text-foreground">{__('general.total_debited_balance')}</span>
                                                <span className="font-bold text-lg text-primary">
                                                    {Number(parseFloat(previewData.amount) + parseFloat(previewData.fee)).toFixed(2)} {previewData.currency}
                                                </span>
                                            </div>

                                            <div className="p-4 flex items-center justify-between text-sm">
                                                <span className="text-muted-foreground">{__('general.recipient_will_receive')}</span>
                                                <span className="font-bold text-emerald-600 text-lg">
                                                    {Number(previewData.converted_amount).toFixed(2)} {previewData.converted_currency}
                                                </span>
                                            </div>

                                            <div className="p-4 bg-muted/10 flex items-center justify-between text-xs text-muted-foreground">
                                                <span>Your remaining daily transfer limit:</span>
                                                <span className="font-semibold">{Number(previewData.remaining_limit).toFixed(2)} {previewData.currency}</span>
                                            </div>
                                        </div>
                                    ) : null}

                                    {/* Action Buttons */}
                                    <div className="flex items-center justify-between pt-4">
                                        <Button type="button" variant="outline" className="shadow-none" onClick={() => setStep(1)}>
                                            <ArrowLeft className="w-4 h-4 me-2" /> Back
                                        </Button>
                                        <Button
                                            type="button"
                                            className="shadow-none"
                                            disabled={!previewData || !!previewError}
                                            onClick={() => setStep(3)}
                                        >{__('general.next_step')}<ArrowRight className="w-4 h-4 ms-2" />
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {/* STEP 3: Confirm Transfer */}
                            {step === 3 && selectedRecipient && previewData && (
                                <div className="space-y-6">
                                    <Alert className="shadow-none bg-amber-50 border-amber-200 text-amber-900">
                                        <AlertCircle className="h-4 w-4 text-amber-700" />
                                        <AlertTitle className="font-semibold">{__('general.irreversible_transaction')}</AlertTitle>
                                        <AlertDescription className="text-xs">{__('general.peer_to_peer_wallet_transfers_are_direct_absolute_and_cannot_be_refunded_or_cancelled_once_processed_please_verify_the_recipient_email_carefully_before_proceeding')}</AlertDescription>
                                    </Alert>

                                    {/* Summary Invoice Receipt Preview */}
                                    <div className="p-6 border border-dashed rounded-lg bg-muted/10 space-y-4">
                                        <h3 className="font-bold text-center text-sm tracking-wide uppercase text-muted-foreground">{__('general.transfer_confirmation')}</h3>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Sender User:</span>
                                                <span className="font-medium text-foreground">{user.name} ({user.email})</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Recipient User:</span>
                                                <span className="font-medium text-primary">{selectedRecipient.name} ({selectedRecipient.email})</span>
                                            </div>
                                            <div className="flex justify-between border-t pt-2">
                                                <span className="text-muted-foreground">Transfer Principal:</span>
                                                <span className="font-bold">{Number(previewData.amount).toFixed(2)} {previewData.currency}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-muted-foreground">Calculated Fee:</span>
                                                <span className="font-medium">+{Number(previewData.fee).toFixed(2)} {previewData.currency}</span>
                                            </div>
                                            <div className="flex justify-between border-t border-primary/20 pt-2 text-foreground font-bold">
                                                <span>Total Debit Amount:</span>
                                                <span>{Number(parseFloat(previewData.amount) + parseFloat(previewData.fee)).toFixed(2)} {previewData.currency}</span>
                                            </div>
                                            <div className="flex justify-between text-emerald-600 font-bold">
                                                <span>Recipient Credit Amount:</span>
                                                <span>{Number(previewData.converted_amount).toFixed(2)} {previewData.converted_currency}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Confirmation Checkbox */}
                                    <div className="flex items-start space-x-3 p-4 border rounded-md bg-muted/5">
                                        <input
                                            id="confirm_transfer"
                                            type="checkbox"
                                            className="mt-1 h-4 w-4 rounded border-gray-300 text-primary focus:ring-primary"
                                            checked={data.confirm_transfer}
                                            onChange={(e) => setData('confirm_transfer', e.target.checked)}
                                        />
                                        <div className="space-y-1">
                                            <Label htmlFor="confirm_transfer" className="font-medium text-sm text-foreground">
                                                I authorize this transfer of {Number(previewData.amount).toFixed(2)} {previewData.currency} to {selectedRecipient.name}.
                                            </Label>
                                            <p className="text-xs text-muted-foreground">{__('general.i_confirm_the_recipient_details_are_correct_and_accept_that_this_transaction_is_immediate_and_final')}</p>
                                        </div>
                                    </div>
                                    {errors.confirm_transfer && (
                                        <p className="text-sm text-rose-600 font-medium">{errors.confirm_transfer}</p>
                                    )}

                                    {/* Action Buttons */}
                                    <div className="flex items-center justify-between pt-4">
                                        <Button type="button" variant="outline" className="shadow-none" onClick={() => setStep(2)}>
                                            <ArrowLeft className="w-4 h-4 me-2" /> Back
                                        </Button>
                                        <Button
                                            type="submit"
                                            className="shadow-none bg-primary hover:bg-primary/95 text-primary-foreground font-semibold px-6"
                                            disabled={processing || !data.confirm_transfer}
                                        >
                                            {processing ? 'Processing Transfer...' : (
                                                <>{__('general.execute_transfer')}<Send className="w-4 h-4 ms-2" />
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </div>
                            )}

                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
