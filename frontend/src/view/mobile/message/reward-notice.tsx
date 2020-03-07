import * as React from 'react';
import { API, ResData, ReqData } from '../../../config/api';
import { MobileRouteProps } from '../router';
import { Page } from '../../components/common/page';
import { NavBar } from '../../components/common/navbar';
import { List } from '../../components/common/list';
import { MarkAllAsRead } from './mark-all-as-read';
import { RewardItem } from './reward-item';

interface State {
  rewardsReceived:API.Get['/user/$0/reward_received'];
  rewardsSent:API.Get['/user/$0/reward_sent'];
}

export class RewardNotice extends React.Component<MobileRouteProps, State> {
  public state:State = {
    rewardsReceived: [],
    rewardsSent: [],
  };

  public async componentDidMount() {
    const { getUserRewardsReceived, getUserRewardsSent } = this.props.core.db;
    const fetchRewardsReceived = getUserRewardsReceived()
      .catch((e) => {
        console.log(e);
        return this.state.rewardsReceived;
      });
    const fetchRewardsSent = getUserRewardsSent()
      .catch((e) => {
        console.log(e);
        return this.state.rewardsSent;
      });
    const [rewardsReceived, rewardsSent] = await Promise.all([fetchRewardsReceived, fetchRewardsSent]);
    this.setState({rewardsReceived, rewardsSent});
    console.log(rewardsReceived, rewardsSent);
  }

  public deleteReward = (rewardId:number) => async () => {
    try {
      await this.props.core.db.deleteReward(rewardId);
      let rewardsSent = this.state.rewardsSent;
      rewardsSent.splice(rewardsSent.findIndex( (r) => r.id == rewardId), 1);
      this.setState({rewardsSent});

      // due to pagination, after we delete a reward, we have space for reward in page 2
      rewardsSent = await this.props.core.db.getUserRewardsSent();
      this.setState({rewardsSent});
    } catch (e) {
      console.log(e);
    }
  }

  public render () {
    return (<Page className="msg-page"
        top={<NavBar goBack={this.props.core.route.back}>
          打赏提醒
        </NavBar>}>

        < MarkAllAsRead />

        { this.renderRewards() }
      </Page>);
  }

  private getRewards() {
    return [...this.state.rewardsReceived, ...this.state.rewardsSent]
      .sort((r1, r2) => {
        const d1 = new Date(r1.attributes.created_at);
        const d2 = new Date(r2.attributes.created_at);
        return (d2.getTime() - d1.getTime());
      });
  }
  private renderRewards () {
    const rewards = this.getRewards();
    return (
      <List className="message-list">
        {rewards.map((d) =>
          <RewardItem
            key={d.id}
            read={false}
            reward={d}
            userId={this.props.core.user.id}
            deleteReward={this.deleteReward}
          />)}
      </List>);
  }
}