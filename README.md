# Cron Commander

View and control your WP-Cron schedule from the WordPress admin.

## Plugin URI

https://github.com/humayun-sarfraz/cron-commander

## Author

Humayun Sarfraz  
https://github.com/humayun-sarfraz

## Description

Cron Commander lets you:

- See all scheduled WP-Cron hooks, their next run times, and recurrence.
- Start (single run) or stop (clear) any cron hook via AJAX.
- Use a clean, table-based interface under **Tools → Cron Commander**.
- Benefit from secure nonces and capability checks.

## Installation

1. Upload the `cron-commander` folder to `/wp-content/plugins/`.  
2. Activate **Cron Commander** via **Plugins**.  
3. Navigate to **Tools → Cron Commander** to view and manage scheduled tasks.

## Usage

- Click **Stop** to clear a hook’s schedule.  
- Click **Start** to schedule a one-time run 60 seconds later.  
- Button labels automatically update to reflect the new state.

## Screenshots

1. **Dashboard view** of all hooks  
2. **Stop/Start** buttons toggling state  

## Changelog

### 1.0.0
- Initial release: view, start, and stop WP-Cron hooks.

## Frequently Asked Questions

**Q: Can I schedule a recurring run from here?**  
A: Not yet—Cron Commander currently supports one-time start events only.

**Q: What happens if I stop a recurring hook?**  
A: It will clear all future schedules for that hook. You can re-start it as a one-time event.

## Contributing

1. Fork the repo:  
   ```bash
   git clone https://github.com/humayun-sarfraz/cron-commander.git

2. Create a branch:
   ```bash
   git checkout -b feature/your-feature

3. Commit and push your changes.

4. Open a Pull Request.

License

GPL v2 or later — see LICENSE.
