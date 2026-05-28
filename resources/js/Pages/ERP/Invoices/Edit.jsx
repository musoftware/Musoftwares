/**
 * Edit.jsx — Standalone page component for editing invoices.
 * Wraps the same CreateEdit component used by Create.jsx.
 */
import React from 'react';
import CreateEdit from './Create';

export default function Edit(props) {
    return <CreateEdit {...props} />;
}

