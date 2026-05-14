# Architecture

This package follows a layered architecture.

## Layers

- `src/Application`: orchestration logic and action use-cases.
- `src/Domain`: contracts, DTOs, validation, core rules, and domain exceptions.
- `src/Infrastructure`: adapters and integration-specific implementations.

## Main Flow

1. `InsertCustomerService` receives command payload.
2. Action class (`CreateCustomerAction`, `UpdateCustomerAction`, etc.) coordinates operation steps.
3. `CustomerServiceSupport` contains shared domain workflows (mapping, querying, persistence checks).
4. `CustomerConnectorInterface` is used to call external ARTSOFT endpoints.
5. Result is returned as `CustomerOperationResult`.

## Design Notes

- Validation happens before mutations.
- DTOs isolate external payload shapes from application logic.
- Infrastructure adapters are replaceable through dependency injection.
