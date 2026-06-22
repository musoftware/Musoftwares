import React from 'react';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle, CardDescription, CardFooter } from '@/components/ui/card';
import { formatMoney } from '@/lib/utils';
import { CheckCircle, Clock, CheckSquare } from 'lucide-react';
import { __ } from '@/lib/i18n';

interface Contract {
    uuid: string;
    reference: string;
    client_name: string;
    project_name: string;
    total_amount: number;
    currency: string;
    contract_text: string;
    status: string;
    client_signature: string | null;
    signed_at: string | null;
    valid_until: string | null;
}

interface Props {
    contract: Contract;
}

export default function ContractView({ contract }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        signature_name: '',
    });

    const isSigned = contract.status === 'signed' || contract.client_signature !== null;

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('client-portal.contracts.sign', contract.uuid));
    };

    return (
        <div className="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
            <Head title={`Contract: ${contract.project_name}`} />

            <div className="max-w-4xl mx-auto">
                <div className="text-center mb-10">
                    <h1 className="text-3xl font-extrabold text-gray-900 tracking-tight">{__('general.project_contract')}</h1>
                    <p className="mt-2 text-sm text-gray-500">Reference: {contract.reference || `CTR-${contract.uuid.split('-')[0]}`}</p>
                </div>

                <div className="bg-white shadow-xl rounded-xl overflow-hidden border border-gray-200">
                    <div className="p-8 border-b border-gray-100 bg-gray-50/50 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                        <div>
                            <h2 className="text-xl font-bold text-gray-900">{contract.project_name}</h2>
                            <p className="text-gray-600 mt-1">Prepared for: <span className="font-semibold text-gray-900">{contract.client_name}</span></p>
                        </div>
                        <div className="text-start md:text-end">
                            <p className="text-sm text-gray-500 uppercase tracking-wider font-semibold">{__('general.total_amount')}</p>
                            <p className="text-3xl font-bold text-gray-900 mt-1">{formatMoney(contract.total_amount, contract.currency)}</p>
                        </div>
                    </div>

                    <div className="p-8 prose prose-slate max-w-none">
                        <div className="whitespace-pre-wrap font-serif text-gray-800 leading-relaxed text-sm md:text-base border p-8 rounded-lg bg-[#fafafa]">
                            {contract.contract_text || "No contract terms provided."}
                        </div>
                    </div>

                    <div className="bg-gray-50 p-8 border-t border-gray-200">
                        {isSigned ? (
                            <div className="rounded-lg bg-green-50 p-6 border border-green-200">
                                <div className="flex items-center">
                                    <CheckCircle className="h-8 w-8 text-green-500 me-4" />
                                    <div>
                                        <h3 className="text-lg font-medium text-green-800">{__('general.contract_signed')}</h3>
                                        <p className="mt-1 text-sm text-green-700">{__('general.signed_by')}<span className="font-semibold">{contract.client_signature}</span> on {new Date(contract.signed_at!).toLocaleString()}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        ) : (
                            <Card className="border-0 shadow-none bg-transparent">
                                <CardHeader className="px-0 pt-0">
                                    <CardTitle className="text-xl flex items-center">
                                        <CheckSquare className="me-2 h-5 w-5 text-gray-700" />{__('general.digital_signature')}</CardTitle>
                                    <CardDescription className="text-base text-gray-600">{__('general.by_typing_your_full_name_below_and_clicking_sign_contract_you_agree_to_be_legally_bound_by_the_terms_above')}</CardDescription>
                                </CardHeader>
                                <form onSubmit={submit}>
                                    <CardContent className="px-0 pb-6">
                                        <div className="max-w-md">
                                            <Label htmlFor="signature_name" className="text-sm font-semibold text-gray-700">{__('general.full_legal_name')}</Label>
                                            <Input
                                                id="signature_name"
                                                value={data.signature_name}
                                                onChange={e => setData('signature_name', e.target.value)}
                                                placeholder={`e.g., ${contract.client_name}`}
                                                className="mt-2 text-lg py-6"
                                                required
                                            />
                                            {errors.signature_name && <p className="text-sm text-red-600 mt-2">{errors.signature_name}</p>}
                                        </div>
                                    </CardContent>
                                    <CardFooter className="px-0 pb-0">
                                        <Button type="submit" disabled={processing} size="lg" className="w-full sm:w-auto bg-black hover:bg-gray-800 text-white px-8 h-12 text-lg">
                                            {processing ? 'Signing...' : 'Sign Contract'}
                                        </Button>
                                    </CardFooter>
                                </form>
                            </Card>
                        )}
                    </div>
                </div>

                <div className="mt-8 text-center text-sm text-gray-500">
                    <p>{__('general.powered_by')}<span className="font-semibold text-gray-900">{__('general.musoftware')}</span>{__('general.freelance_isaas')}</p>
                </div>
            </div>
        </div>
    );
}
