import { useState } from "react";
import BudgetGoals from "@/components/BudgetGoals";
import QuickActions from "@/components/QuickActions";
import Sidebar from "@/components/Sidebar";
import SpendingChart from "@/components/SpendingChart";
import StatCard from "@/components/StatCard";
import Topbar from "@/components/Topbar";
import TransactionList from "@/components/TransactionList";
import UpcomingBills from "@/components/UpcomingBills";
import BalanceCard from "@/components/BalanceCard";

const Dashboard = () => {
    const [sidebarOpen, setSidebarOpen] = useState(false);
    const [activeNav, setActiveNav] = useState('overview');

    const handleNavClick = (id: string) => {
        setActiveNav(id);
        setSidebarOpen(false);
    };

    return (
        <div className="app">
            <Sidebar isOpen={sidebarOpen} activeNav={activeNav} onNavClick={handleNavClick} />

            <div
                className={`overlay${sidebarOpen ? ' show' : ''}`}
                onClick={() => setSidebarOpen(false)}
            />

            <main className="main">
                <Topbar onMenuClick={() => setSidebarOpen(true)} />

                <section className="hero-row">
                <BalanceCard />
                <StatCard
                    label="Income"
                    amount="₦640,200"
                    deltaLabel="+12%"
                    deltaPositive
                    iconName="arrow-up"
                    variant="in"
                />
                <StatCard
                    label="Expenses"
                    amount="₦218,650"
                    deltaLabel="−6%"
                    deltaPositive={false}
                    iconName="arrow-down"
                    variant="out"
                />
                </section>

                <section className="content-grid">
                <div className="content-left">
                    <SpendingChart />
                    <TransactionList />
                </div>

                <div className="content-right">
                    <QuickActions />
                    <BudgetGoals />
                    <UpcomingBills />
                </div>
                </section>
            </main>
        </div>
    );
}

export default Dashboard;
