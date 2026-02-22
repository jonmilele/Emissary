# Phase 1: Database Schema Reconstruction

[Back to Index](README.md) | [Next: PHP 8.2 Migration →](02-php82-migration.md)

## Overview

The original MySQL database was lost. The schema was reverse-engineered by analyzing ~55 PHP source files, tracing every SQL query to reconstruct table structures, column names, types, and relationships.

## Tables Created

The full schema lives in [`html/schema.sql`](../html/schema.sql).

### Core Game Tables (17 total)

| Table | Purpose | Key Columns |
|-------|---------|-------------|
| `players` | User accounts | PlayerID, UserName, Password, Email, Metal, Mineral, Astrium, Credits, TeamID, SetupStage |
| `teams` | Player alliances | TeamID, Name, Colour, Leader |
| `sectors` | 10×10 galaxy grid | SectorID, GridCoords, MajOwner |
| `Systems` | Star systems within sectors | SystemID, Name, Orbits, SectorID, Coords |
| `planets` | Planets within systems | PlanetID, Name, Orbit, System, Size, Owner |
| `planet_types` | Planet size definitions | Type, Grids, xstart, ystart, rowsquares, income |
| `buildings` | Built structures on planets | BuildingID, Type, Planet, Owner, HP, GridX, GridY |
| `building_types` | Building definitions | Type, Name, HP, AP, Metal, Mineral, Astrium, Turns, Colour |
| `cbuildings` | Buildings under construction | BuildingID, Type, Planet, Owner, TTF |
| `ships` | Completed ships | ShipID, Type, Fleet, Owner, HP |
| `ship_types` | Ship definitions | Type, Name, HP, AP, Metal, Mineral, Astrium, Turns |
| `cships` | Ships under construction | ShipID, Type, Planet, Owner, TTF |
| `qships` | Queued ship orders | QueueID, Type, Planet, Owner, Position |
| `fleets` | Fleet groups | FleetID, Owner, Location, Destination, Strategy, TTF |
| `battles` | Battle records | BattleID, Location, Attacker, Defender, Result |
| `auctions` | Resource marketplace | AuctionID, Seller, ResourceType, Amount, Price |
| `gamelog` | Event log | LogID, PlayerID, Message, Timestamp |

### Reference Data Seeded

- **100 sectors** with grid coordinates (`1,1` through `10,10`)
- **4 planet types** with grid layout definitions (sizes 1–4)
- **9 building types**: HQ, Mine, Refinery, Shipyard, Turret, Shield, Hangar, Barracks, Sensor
- **7 ship types**: Scout, Fighter, Bomber, Cruiser, Battleship, Colony Ship, Transport

> **Note:** Building and ship stat values (HP, AP, resource costs, build times) are estimates based on game logic patterns. The original values were not recoverable from the PHP source alone.

## Schema Design Decisions

- `Password` column uses `VARBINARY(255)` to accommodate `password_hash()` output (PHP 8.2 migration)
- `System` column name is a MySQL 8 reserved word — always backtick-quoted in queries
- Auto-increment IDs on all primary keys
- No foreign key constraints (matching original game's design pattern)
