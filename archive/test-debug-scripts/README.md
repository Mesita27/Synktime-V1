# Archived Test and Debug Scripts

This directory contains test, debug, and migration scripts from the original Synktime/ directory that are kept for historical reference but are not part of the active codebase structure.

## Contents

### Database Check Scripts
Scripts for verifying database structure and relationships.

### Migration Scripts
One-time migration scripts for database schema changes.

### Test Scripts
Development test scripts for various features.

### Debug Scripts
Diagnostic tools for troubleshooting.

### Setup/Deploy Scripts
Legacy deployment and setup utilities.

## Usage

These scripts are archived for reference only. They may not work with the new structure without modifications.

If you need to use any of these scripts:
1. Review the script to understand its purpose
2. Update paths to match new structure
3. Test in a development environment first
4. Consider if the functionality should be integrated into the main codebase

## Categories

### check_*.php
Database structure verification scripts

### create_*.php
Database table/data creation scripts

### test_*.php, *_test.php
Feature testing scripts

### clean_*.php
Data cleanup utilities

### migrate_*.php, run_migration*.php
Database migration scripts

### diagnose*.php, diagnostico*.php
System diagnostic tools

### deploy_*.php, *.sh, *.bat
Deployment utilities

### V05_*.php
Legacy version 05 files

### Other utilities
Various setup and configuration scripts

## Date Archived

2025-11-21

## Notes

- These files are preserved for historical reference
- May contain useful logic for understanding legacy behavior
- Should not be used in production without review and updates
- Consider migrating useful functionality to proper test suites
