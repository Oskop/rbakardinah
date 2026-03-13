<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TransactionalDataSeeder extends Seeder
{
    public function run(): void
    {
        // Table: rba_headers
        DB::table('rba_headers')->insert(array (
  0 => 
  array (
    'id' => 1,
    'period_id' => 1,
    'admin_id' => 1,
    'year' => '2026',
    'status_global' => 'Draft',
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
));

        // Table: rba_submissions
        DB::table('rba_submissions')->insert(array (
  0 => 
  array (
    'id' => 1,
    'rba_header_id' => 1,
    'unit_id' => 1,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
  1 => 
  array (
    'id' => 2,
    'rba_header_id' => 1,
    'unit_id' => 2,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
  2 => 
  array (
    'id' => 3,
    'rba_header_id' => 1,
    'unit_id' => 3,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
  3 => 
  array (
    'id' => 4,
    'rba_header_id' => 1,
    'unit_id' => 4,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
  4 => 
  array (
    'id' => 5,
    'rba_header_id' => 1,
    'unit_id' => 5,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
  5 => 
  array (
    'id' => 6,
    'rba_header_id' => 1,
    'unit_id' => 6,
    'status_submission' => 'Draft',
    'supervisor_note' => NULL,
    'created_at' => '2026-03-12 04:13:17',
    'updated_at' => '2026-03-12 04:13:17',
  ),
));

        // No data found for table: rba_account_pagus
        // Table: rba_details
        DB::table('rba_details')->insert(array (
  0 => 
  array (
    'id' => 1,
    'rba_submission_id' => 4,
    'account_code_id' => 74,
    'description' => 'server HP with GPU',
    'nominal_request' => '100000000000.00',
    'is_submitted' => 0,
    'is_validated' => 0,
    'validated_at' => NULL,
    'created_by' => 54,
    'created_at' => '2026-03-12 04:28:06',
    'updated_at' => '2026-03-12 04:28:06',
    'validated_by' => NULL,
    'is_rejected' => 0,
    'rejected_at' => NULL,
    'rejected_by' => NULL,
    'rejection_reason' => NULL,
    'deleted_at' => NULL,
  ),
  1 => 
  array (
    'id' => 2,
    'rba_submission_id' => 4,
    'account_code_id' => 73,
    'description' => 'Fortigate 401F',
    'nominal_request' => '475000000.00',
    'is_submitted' => 0,
    'is_validated' => 0,
    'validated_at' => NULL,
    'created_by' => 54,
    'created_at' => '2026-03-12 04:36:13',
    'updated_at' => '2026-03-12 04:36:13',
    'validated_by' => NULL,
    'is_rejected' => 0,
    'rejected_at' => NULL,
    'rejected_by' => NULL,
    'rejection_reason' => NULL,
    'deleted_at' => NULL,
  ),
));

        // Table: rba_attachments
        DB::table('rba_attachments')->insert(array (
  0 => 
  array (
    'id' => 1,
    'rba_detail_id' => 1,
    'file_path' => 'attachments/WBvkuSOAfETf25XPtr55dsGHwWHhZUGMmuXRNffQ.pdf',
    'version_number' => 1,
    'uploaded_by' => 54,
    'created_at' => '2026-03-12 04:28:06',
    'updated_at' => '2026-03-12 04:28:06',
  ),
  1 => 
  array (
    'id' => 2,
    'rba_detail_id' => 2,
    'file_path' => 'attachments/kfOYfiaN6CtDQlvQGlgSN0u4zToMcAdwjNHIUGwk.pdf',
    'version_number' => 1,
    'uploaded_by' => 54,
    'created_at' => '2026-03-12 04:36:13',
    'updated_at' => '2026-03-12 04:36:13',
  ),
));

    }
}
